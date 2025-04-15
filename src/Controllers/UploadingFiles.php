<?php

namespace Ultra\UploadManager\Controllers;

use Ultra\UploadManager\Events\FileProcessingUpload;

use Ultra\UploadManager\Exceptions\VirusException;
use Ultra\UploadManager\Exceptions\CustomException;
use Ultra\UploadManager\Jobs\DeleteTempFolder;
// use App\Models\Teams_item;
use Ultra\UploadManager\Services\TestingConditionsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Util\FileHelper;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Exception;
use Fabio\UltraLogManager\Facades\UltraLog;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Process\Process;
use Illuminate\Routing\Controller;
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;
use Ultra\UploadManager\Traits\TestingTrait;

class UploadingFiles extends Controller
{
    use HasValidation, HasUtilitys, TestingTrait;

    protected $user_id;
    protected $current_team;
    protected $path_image;
    protected $floorPrice;
    protected $team_id;
    protected $channel = 'upload';


    /**
     * Gestisce l'upload di un file, inclusa la validazione, la creazione del record nel database,
     * e il salvataggio del file sia in locale che su uno spazio esterno (ad es. DigitalOcean Spaces).
     *
     * Il metodo esegue le seguenti operazioni:
     *  - Verifica dei permessi dell'utente per creare un EGI.
     *  - Valida il file caricato dall'utente.
     *  - Crea il record EGI nel database.
     *  - Salva il file sia localmente che su uno spazio di archiviazione esterno.
     *  - Invia notifiche di stato all'utente tramite broadcasting durante il processo.
     *  - Gestisce eventuali errori, eseguendo rollback in caso di fallimenti.
     *
     * @param Request $request La richiesta HTTP contenente il file da caricare e altre informazioni.
     *
     * @return \Illuminate\Http\JsonResponse Restituisce una risposta JSON con i dettagli dello stato del processo:
     *      - Se il caricamento è completato, restituisce un messaggio di successo e lo stato.
     *      - Se si verifica un errore, restituisce un messaggio di errore, il codice di errore, e lo stato.
     *
     * @throws \Exception Se si verifica un errore durante il salvataggio del file o il processo di upload.
     *      Gli errori imprevisti vengono catturati e gestiti tramite il dispatcher degli errori, che notifica il team di sviluppo.
     */

    public function upload(Request $request): mixed
    {

        // Inizializzazione del log
        $encodedLogParams = json_encode([
            'Class' => 'UploadingFiles',
            'Method' => 'upload',
        ]);

        // UltraLog::log('info', 'INIZIO UPLOAD', "");

        // Verifica i permessi dell'utente
        // $this->validateUserPermissions();

        $state = "";
        $this->user_id = Auth::id();
        $this->current_team = Auth::user()->currentTeam;
        $this->team_id = $this->current_team->id;
        $this->floorPrice = $this->current_team->floorPrice ?: 0;

        try {

            // Reimposta il singleton prima di ogni test

            if ($request->hasFile('file')) {

                $file = $request->file('file');

                // trasforma in intero l'indice
                $index = $request->input('index');

                $nomeFile = $file->getClientOriginalName();

                $finished = filter_var($request->input('finished'), FILTER_VALIDATE_BOOLEAN);

                // UltraLog::log('info', 'Operation Finished', "finished: $finished");

                // Log::channel('upload')->info($encodedLogParams, ['Action' => 'finished', 'finished' => $finished]);

                Log::channel('upload')->info($encodedLogParams, ['Action' => 'File caricato', 'file' => $nomeFile]);

                // Simula un errore di file non trovato
                if (TestingConditionsManager::getInstance()->isTesting('FILE_NOT_FOUND') && $index == 0) {
                    $nomeFile .= '1'; // Rinomina il file per simulare un errore
                }

                if (TestingConditionsManager::getInstance()->isTesting('GENERIC_SERVER_ERROR') && $index == 0) {
                    Log::channel('upload')->error($encodedLogParams, ['Action' => 'Simulazione errore server']);
                    throw new CustomException('GENERIC_SERVER_ERROR');
                }

                // Validazione del file
                $this->validateFile($file, $index);

                // Stato validazione file
                $this->dispatchStatusToUser("validation", __("label.im_checking_the_validity_of_the_file"), $nomeFile);

                // Validazione e creazione record EGI
                $fileDetailsArray = $this->prepareAndSaveEGIRecord($file);

                // Stato di salvataggio delle informazioni sul database
                $this->dispatchStatusToUser("info", __("label.im_recording_the_information_in_the_database"), $nomeFile);

                // Salvataggio del file su spazio configurato
                $this->saveFileToSpaces($fileDetailsArray['destinationPathImage'] . "/" . $fileDetailsArray['fileName'], $fileDetailsArray['tempRealPath'], $fileDetailsArray['destinationPathImage']);

                // Stato di completamento del processo
                if ($finished) {

                    Log::channel($this->channel)->info($encodedLogParams, ['Action' => 'finished upload', 'file' => $nomeFile]);

                    $iterFailed = $request->input('iterFailed');
                    Log::channel($this->channel)->info($encodedLogParams, ['Action' => 'iterFailed', 'iterFailed' => $iterFailed]);
                    Log::channel($this->channel)->info($encodedLogParams, ['Action' => 'index', 'index' => $index]);

                    if ($iterFailed === '0') {

                        $message = __('label.all_files_are_saved');
                        $state = "allFileSaved";

                    }else{

                        if ($iterFailed === $index){
                            $message = __('label.upload_failed');
                            $state = "uploadFailed";
                        }else{
                            $message = __('label.some_errors');
                            $state = "finishedWithSameError";
                        }

                    }

                    $this->dispatchStatusToUser($state, $message, $nomeFile);

                    return response()->json([
                        'userMessage' => __("label.file_saved_successfully", ['fileCaricato' => $nomeFile]),
                        'state' => $state,
                        'file' => $fileDetailsArray['fileName'],
                        'virusFound' => false,
                        'someInfectedFiles' => false,
                    ], 200);

                } else {

                    Log::channel($this->channel)->error($encodedLogParams, ['Action' => 'singolo file salvato', 'file' => $nomeFile]);
                    $this->dispatchStatusToUser("processSingleFileCompleted", __("label.file_saved_successfully", ['fileCaricato' => $nomeFile]), $nomeFile);
                    return response()->json([
                        'userMessage' => __("label.file_saved_successfully", ['fileCaricato' => $nomeFile]),
                        'state' => "processSingleFileCompleted",
                        'file' => $fileDetailsArray['fileName'],
                        'virusFound' => false,
                        'someInfectedFiles' => false,
                    ], 200);
                }

            } else {
                $state = "noFile";
                $message = __('label.no_file_uploaded');
                FileProcessingUpload::dispatch($message, $state, Auth::id());
                Log::channel($this->channel)->error($encodedLogParams, ['Action' => $message]);
                return response()->json([
                    'userMessage' => $message,
                    'state' => $state,
                    'file' => null,
                    'virusFound' => false,
                    'someInfectedFiles' => false,
                ], 404);  // <- Questo gestisce l'assenza di file
            }

        } catch (\Exception $e) {

            Log::channel($this->channel)->error($encodedLogParams, ['Action' => 'Errore durante il caricamento del file', 'error' => $e->getMessage()]);

            // Raccogli i dettagli del file se disponibili
            $fileDetailsArray = $fileDetailsArray ?? [];

            // Esegui i rollback necessari
            $this->performRollback($fileDetailsArray);

            // Invia un messaggio di stato tramite broadcasting
            FileProcessingUpload::dispatch($e->getMessage(), "error", Auth::id());

            // Rilancia l'eccezione per farla gestire dal Handler di Laravel
            throw $e;
        }

    }

    private function validateUserPermissions()
    {
        $encodedLogParams = json_encode([
            'Class' => 'UploadingFiles',
            'Method' => 'validateUserPermissions',
        ]);

        if (!Auth::user()->can('create own EGI')) {
            Log::channel($this->channel)->info($encodedLogParams, [
                'Action' => 'Unauthorized action attempt by user',
                'user_id' => Auth::id()
            ]);
            abort(403, 'Unauthorized action.');
        }
    }

    private function performRollback(array $fileDetailsArray)
    {
        $encodedLogParams = json_encode([
            'Class' => 'UploadingFiles',
            'Method' => 'performRollback',
        ]);

        Log::channel($this->channel)->info($encodedLogParams, [
            'Action' => 'Esecuzione del rollback',
            'fileDetails' => $fileDetailsArray
        ]);

        // Verifica se è stato salvato su DigitalOcean Spaces e lo elimina
        if (!empty($fileDetailsArray['path_external'])) {
            if (Storage::disk('do')->exists($fileDetailsArray['path_external'])) {
                Storage::disk('do')->delete($fileDetailsArray['path_external']);
                Log::channel($this->channel)->info($encodedLogParams, [
                    'Action' => 'File eliminato da DigitalOcean Spaces',
                    'path_external' => $fileDetailsArray['path_external']
                ]);
            } else {
                Log::channel($this->channel)->warning($encodedLogParams, [
                    'Action' => 'File non trovato su DigitalOcean Spaces per l\'eliminazione',
                    'path_external' => $fileDetailsArray['path_external']
                ]);
            }
        }

        // Verifica se è stato salvato localmente e lo elimina
        if (!empty($fileDetailsArray['path_public'])) {
            if (Storage::disk('public')->exists($fileDetailsArray['path_public'])) {
                Storage::disk('public')->delete($fileDetailsArray['path_public']);
                Log::channel($this->channel)->info($encodedLogParams, [
                    'Action' => 'File eliminato dal disco pubblico locale',
                    'path_public' => $fileDetailsArray['path_public']
                ]);
            } else {
                Log::channel($this->channel)->warning($encodedLogParams, [
                    'Action' => 'File non trovato sul disco pubblico per l\'eliminazione',
                    'path_public' => $fileDetailsArray['path_public']
                ]);
            }
        }
    }

    private function prepareAndSaveEGIRecord($file): array
    {
        $encodedLogParams = json_encode([
            'Class' => 'UploadingFiles',
            'Method' => 'prepareAndSaveEGIRecord',
        ]);

        // Preparo l'array che conterrà i dati necessari per creare il record EGI e il salvataggio del file
        $fileDetailsArray = $this->prepareFileDetails($file);

        // Creo il record e salvo su di esso i dati del file
        $egiRecord = $this->createEGIRecord($fileDetailsArray, $fileDetailsArray['extension']);
        Log::channel($this->channel)->info($encodedLogParams, [
            'Action' => 'Record EGI creato',
            'record' => $egiRecord
        ]);

        // Aggiungo il nome del file ai dettagli del file
        $fileDetailsArray['fileName'] = $egiRecord->id . "." . $fileDetailsArray['extension'];

        return $fileDetailsArray;
    }


    private function dispatchStatusToUser(string $state, string $message, string $fileName)
    {
        $encodedLogParams = json_encode([
            'Class' => 'UploadingFiles',
            'Method' => 'dispatchStatusToUser',
        ]);

        FileProcessingUpload::dispatch($message, $state, Auth::id());
        Log::channel($this->channel)->info($encodedLogParams, [
            'Action' => 'Dispatch status to user',
            'state' => $state,
            'message' => $message,
            'file' => $fileName
        ]);
    }

    public function notifyUploadComplete(Request $request)
    {
        $fileName = $request->input('fileName');
        $fileUrl = $request->input('fileUrl');

        // Logica per copiare il file in una cartella temporanea locale per la validazione e la scansione antivirus
        Log::info("File uploaded: $fileName at $fileUrl");

        // Invia il job per la scansione del virus
        // ScanVirusJob::dispatch($fileUrl, $fileName, auth()->user());

        return response()->json(['message' => 'File upload completed']);
    }

    /**
     * Crea un nuovo record EGI nel database.
     *
     * @param array $fileDetailsArray Dettagli del file
     * @param string $extension Estensione del file
     * @return Teams_item Nuovo record EcoNFT creato
     */
    protected function createEGIRecord($fileDetailsArray, $extension)
    {
        // $EGI = new Teams_item();
        // $EGI->fill([
        //     'user_id' => $this->user_id,
        //     'owner_id' => $this->user_id,
        //     'creator' => $this->current_team->creator,
        //     'owner_wallet' => $this->current_team->creator,
        //     'upload_id' => $this->generateUploadId(),
        //     'extension' => $extension,
        //     'file_hash' => $fileDetailsArray['hash_filename'],
        //     'file_mime' => $fileDetailsArray['mimeType'],
        //     'position' => $fileDetailsArray['num'],
        //     'title' => $fileDetailsArray['default_name'],
        //     'team_id' => $this->current_team->id,
        //     'file_crypt' => $fileDetailsArray['crypt_filename'],
        //     'type' => $fileDetailsArray['fileType'],
        //     'bind' => 0,
        //     'price' => $this->floorPrice,
        //     'floorDropPrice' => $this->floorPrice,
        //     'show' => true,
        // ]);

        // $this->setFileDimensions($EGI, $fileDetailsArray['tempRealPath']);
        // $this->setFileSize($EGI, $fileDetailsArray['tempRealPath']);
        // $this->setFileMediaProperties($EGI);

        // try {

        //     $EGI->save();

        //     if (TestingConditionsManager::getInstance()->isTesting('ERROR_DURING_CREATE_EGI_RECORD')) {
        //         Log::channel($this->channel)->info('Classe: UploadinfFiles. Method: createEGIRecord. Action: Simulazione errore durante salvataggio del record EGI');
        //         throw new CustomException('ERROR_DURING_CREATE_EGI_RECORD');
        //     }

        // } catch (\Exception $e) {

        //     Log::channel($this->channel)->info('Classe: UploadinfFiles. Method: createEGIRecord. Action: Errore durante il salvataggio del record EGI', ['error' => $e->getMessage()]);
        //     throw new CustomException('ERROR_DURING_CREATE_EGI_RECORD');
        // }

        // return $EGI;
    }

    /**
     * Genera un ID univoco per l'upload corrente.
     *
     * @return string ID univoco
     */
    protected function generateUploadId()
    {
        return sha1(uniqid(mt_rand(), true));
    }

    /**
     * Determina il tipo di file in base all'estensione.
     *
     * @param string $extension Estensione del file
     * @return string Tipo di file
     */
    protected function getFileType($extension)
    {
        $allowedTypes = config('AllowedFileType.collection.allowed');
        return $allowedTypes[$extension] ?? 'unknown';
    }

    /**
     * Salva il file nello storage degli spazi.
     *
     * @param string $fileToSave Percorso del file da salvare
     * @param string $tempRealPath Percorso temporaneo del file
     * @param string $destinationPathImage Percorso di destinazione del file
     * @return void
     * @throws \Exception Se si verifica un errore durante il salvataggio del file
     */
    public function saveFileToSpaces($fileToSave, $tempRealPath, $destinationPathImage): void
    {
        $channel = 'upload';
        $logParams = [
            'Class' => 'UploadingFiles',
            'Method' => 'saveFileToSpaces',
        ];
        $defaultOstingService = getDefaultHostingService();

        Log::channel($channel)->info('Classe: UploadingFiles. Method: saveFileToSpaces. Action: Path temporaneo', ['$tempRealPath' => $tempRealPath]);
        Log::channel($channel)->info('Classe: UploadingFiles. Method: saveFileToSpaces. Action: File da salvare', ['$fileToSave' => $fileToSave]);
        Log::channel($channel)->info('Classe: UploadingFiles. Method: saveFileToSpaces. Action: Percorso di destinazione', ['$destinationPathImage' => $destinationPathImage]);

        $path_external = null; // Percorso del file su DigitalOcean Spaces
        $path_public = null; // Percorso del file salvato localmente

        // ROUTINE DI TEST per simulare un errore durante il salvataggio del file
        if (TestingConditionsManager::getInstance()->isTesting('UNABLE_TO_SAVE_BOT_FILE')) {
            Log::channel($channel)->error(json_encode($logParams), [
                'Action' => 'Simulazione di errore durante la creazione della directory'
            ]);

            throw new CustomException('UNABLE_TO_SAVE_BOT_FILE');
        }

        // Legge il contenuto del file temporaneo
        $contents = file_get_contents($tempRealPath);
        Log::channel($channel)->info(json_encode($logParams), [
            'Action' => 'Contenuto del file letto correttamente',
            '$fileToSave' => $fileToSave
        ]);

        try {
            // Passo 1: Salvataggio sul disco esterno
            try {
                $path_external = Storage::disk('do')->put($fileToSave, $contents, 'public') ?? '';
                Log::channel($channel)->info(json_encode($logParams), [
                    'Action' => 'File salvato correttamente su ' . $defaultOstingService,
                    '$fileToSave' => $fileToSave
                ]);
            } catch (\Exception $e) {
                Log::channel($channel)->error(json_encode($logParams), [
                    'Action' => 'Errore durante il processo di upload su ' . $defaultOstingService,
                    'error' => $e->getMessage()
                ]);

                throw new CustomException('EXTERNAL_SAVE_FAILED');
            }

            // Passo 2: Salvataggio su localhost

            // Legge il nome della directory in cui salvare il file
            $directory = dirname(Storage::disk('public')->path($destinationPathImage));
            Log::channel($channel)->info(json_encode($logParams), [
                'Action' => 'Verifica esistenza directory',
                'directory' => $directory
            ]);

            Log::channel($channel)->info(json_encode($logParams), [
                'Action' => 'Percorso del file nel metodo upload',
                'percorso_salvataggio' => Storage::disk('public')->path($destinationPathImage)
            ]);

            // Verifica che la directory esista e che abbia i permessi corretti
            $codeError = $this->ensureDirectoryPermissions($directory);

            Log::channel($channel)->info(json_encode($logParams), [
                'Action' => 'Risultato ensureDirectoryPermissions',
                'codeError' => $codeError,
                'directory' => $directory
            ]);

            if ($codeError === 'UNABLE_TO_CREATE_DIRECTORY') {
                // Impossibile creare la directory
                Log::channel($channel)->info(json_encode($logParams), [
                    'Action' => 'Sezione try{}. Errore durante la creazione della directory',
                    'codeError' => $codeError,
                    'directory' => $directory
                ]);

                Log::channel($channel)->error(json_encode($logParams), [
                    'Action' => 'Sezione try{}. Dopo il rollback, la directory non esiste',
                    'directory exist: ' => Storage::disk('public')->exists($directory),
                    'codeError' => $codeError
                ]);

                // Lancio un'eccezione personalizzata
                throw new CustomException('UNABLE_TO_CREATE_DIRECTORY');

            } elseif ($codeError === 'UNABLE_TO_CHANGE_PERMISSIONS') {
                // Impossibile cambiare i permessi della directory
                Log::channel($channel)->error(json_encode($logParams), [
                    'Action' => 'Errore durante il cambio dei permessi della directory',
                    'directory' => $directory,
                    'codeError' => $codeError
                ]);

                // Lancio un'eccezione personalizzata
                throw new CustomException('UNABLE_TO_CHANGE_PERMISSIONS');

            } elseif ($codeError === 'NOT_ERROR') {
                // Nessun errore riscontrato, salvo il file su localhost
                $path_public = Storage::disk('public')->put($fileToSave, $contents, 'public');
                Log::channel($channel)->info(json_encode($logParams), [
                    'Action' => 'File salvato correttamente su localhost',
                    'path_public' => $path_public,
                    'file_exists' => file_exists(Storage::disk('public')->path($fileToSave))
                ]);

                return;
            }

        } catch (CustomException $e) {

            throw $e;

        } catch (\Exception $e) {

            Log::channel($channel)->error(json_encode($logParams), [
                'Action' => 'Sezione catch{}. Errore imprevisto durante il processo di upload',
                'error' => $e->getMessage(),
                'path_public' => $path_public,
            ]);

            // Rollback: elimina il file su DigitalOcean Spaces se esiste
            if (Storage::disk(name: 'do')->exists($path_external)) {
                Storage::disk('do')->delete($path_external);
                Log::channel($channel)->error(json_encode($logParams), [
                    'Action' => 'Sezione catch{}. Rollback: File eliminato da ' . $defaultOstingService,
                    'path_external' => $path_external,
                    'path_public' => $path_public
                ]);
            }

            // Rollback: elimina il file locale se esiste
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->delete($path_public);
                Log::channel($channel)->error(json_encode($logParams), [
                    'Action' => 'Sezione catch{}. Rollback: File eliminato da localhost',
                    'path_public' => $path_public
                ]);
            }

            // Rilancia l'eccezione dopo il rollback
            throw $e;
        }
    }

    public function deleteTemporaryFileLocal(Request $request): mixed
    {

        $channel = 'upload';
        $logParams = json_encode([
            'Class' => 'UploadingFiles',
            'Method' => 'deleteTemporaryFileLocal',
        ]);

        if ($request->hasFile('file')) {
            // Abbiamo un file caricato
            $uploadedFile = $request->file('file');
            $fileName = $uploadedFile->getClientOriginalName();
        } else {
            // Probabilmente abbiamo solo il nome del file
            $fileName = $request->input('fileName', '');

            if (empty($fileName)) {
                Log::channel($channel)->error($logParams, ['Action' => 'Nessun file o nome file fornito']);
                return response()->json([
                    'userMessage' => 'Nessun file specificato',
                    'errorCode' => config('error_constants.ERROR_MISSING_FILE'),
                    'state' => 'error',
                ], 400);
            }
        }

        // Creo il percorso del file
        $filePath = get_temp_file_path($fileName);

        Log::channel($channel)->info($logParams ,  ['Action' => 'Tentativo di eliminazione del file', 'path' => $filePath]);

        try {

            // Simula un errore durante l'eliminazione del file
            if (TestingConditionsManager::getInstance()->isTesting('ERROR_DELETING_LOCAL_TEMP_FILE')) {

                Log::channel($channel)->error($logParams , ['Action' => 'Simulazione errore durante l\'eliminazione del file', 'filePath' => $filePath]);

                // Per lo user utilizzo un messaggio di errore di upload generico
                $errorCode = config('error_constants.ERROR_DELETING_LOCAL_TEMP_FILE'); // Qui $errorCode sarà il codice di errore restituito al client

                throw new CustomException('ERROR_DELETING_LOCAL_TEMP_FILE');

            }

            // Verifica se il file esiste
            if (file_exists($filePath)) {

                Log::channel($channel)->info($logParams , ['Action' => 'File eliminato con successo', 'filePath' => $filePath]);

                // Elimina il file dal disco locale
                unlink($filePath);

                $state = "tempFileDeleted";
                // Per lo user utilizzo un messaggio di errore di upload generico
                $message = __('label.file_deleted_successfully');

                return response()->json([
                    'userMessage' => $message,
                    'state' => $state,
                    'file' => $fileName,
                    'virusFound' => false,
                    'someInfectedFiles' => false,
                ], 200);

            } else {

                // Non lancio un errore ma ritorno una response 404, poiché il flusso di lavoro non deve interrompersi, devo solo registrare l'errore, e lo faccio lato client
                // vedi il metodo deleteTemporaryFileLocal() in uploadinf_files.js. Viene inviata una mail al devTeam attraverso il sistema di gestione errori centralizzato

                Log::channel($channel)->error($logParams , ['Action' => 'Il file non esiste', 'filePath' => $filePath]);

                $state = "error";
                // Non devo inviare nessun messaggio all'utente
                $message = "";
                $errorCode = config('error_constants.ERROR_DELETING_LOCAL_TEMP_FILE'); // Qui $errorCode sarà il codice di errore restituito al client

                return response()->json([
                    'userMessage' => $message,
                    'errorCode' => $errorCode,
                    'state' => $state,
                    'file' => $fileName,
                    'virusFound' => false,
                    'someInfectedFiles' => false,
                ], 404); // Nel caso il file temp da eliminare non esista ritorno un 404. questo errore però non bloccherà il flusso di lavoro. Viene inviata una mail al devTeam attraverso il sistema di gestione errori centralizzato

            }

        } catch (\Exception $e) {

            Log::channel($channel)->error($logParams , ['Action' => ' Errore durante l\'eliminazione del file', 'filePath' => $filePath]);
            // Rilancia l'eccezione per gestire tramite ErrorDispatcher in app/Exceptions/Handler.php
            throw $e;

        }
    }

    public function deleteTemporaryFolder(Request $request)
    {

        $channel ='upload';
        Log::channel($channel)->info('Classe: UploadingFiles. Method: deleteTemporaryFolder. Action: Richiesta di eliminazione della cartella temporanea', ['request' => $request->all()]);

        DeleteTempFolder::dispatch($request->input('folderName'));
    }

    public function deleteTemporaryFileDO(Request $request)
    {

        $channel ='upload';
        $file = $request->input('file');

        if (TestingConditionsManager::getInstance()->isTesting('ERROR_DELETING_LOCAL_TEMP_FILE')) {
            Log::channel($channel)->error('Classe: UploadingFiles. Method: deleteTemporaryFileDO. Action: Simulazione errore durante l\'eliminazione del file locale');
            throw new CustomException('ERROR_DELETING_LOCAL_TEMP_FILE');
        }

        if (TestingConditionsManager::getInstance()->isTesting('ERROR_DELETING_EXT_TEMP_FILE')) {
            Log::channel($channel)->error('Classe: UploadingFiles. Method: deleteTemporaryFileDO. Action: Simulazione errore durante l\'eliminazione del file esterno');
            throw new CustomException('ERROR_DELETING_EXT_TEMP_FILE');
        }


        // Ottieni il nome del file dalla richiesta
        $file = $request->input('file');

        // Verifica che il parametro 'file' esista
        if (!$file) {
            return response()->json(['error' => 'No file specified'], 400);
        }

        Log::channel($channel)->info('Classe: UploadingFiles. Method: deleteTemporaryFileDO. Action: Richiesta di eliminazione del file temporaneo', ['file' => $file]);

        // Configurazione del client S3 per DigitalOcean Spaces
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('app.do_default_region'),
            'endpoint' => config('app.do_endpoint'),
            'credentials' => [
                'key'    => config('app.do_access_key_id'),
                'secret' => config('app.do_secret_access_key'),
            ],
        ]);

        // Ottieni il nome del bucket dalla configurazione
        $bucket = config('app.do_bucket');
        $file = config('app.bucket_temp_file_folder') . '/' . $file;

        try {
            // Eliminazione del file specificato
            $s3Client->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $file,
            ]);

            Log::channel($channel)->info('Classe: UploadingFiles. Method: deleteTemporaryFileDO. Action: File eliminato con successo', ['file' => $file]);

            return response()->json([
                'userMessage' => __('label.file_deleted_successfully'),
                'state' => 'tempFileDeleted',
                'file' => $file,
                'virusFound' => false,
                'sameInfectedFiles' => false

            ], 200);

        } catch (AwsException $e) {
            // Gestione degli errori durante l'eliminazione del file
            Log::channel($channel)->error('Classe: UploadingFiles. Method: deleteTemporaryFileDO. Action: Errore durante l\'eliminazione del file temporaneo', ['error' => $e->getMessage()]);
            throw new CustomException('ERROR_DELETING_LOCAL_TEMP_FILE');
        }
    }




    /**
     * Genera un nome predefinito per l'EGI.
     *
     * @param int $num Numero di posizione
     * @return string Nome predefinito generato
     */
    protected function generateDefaultName($num)
    {
        $first = str_pad($num, 4, '0', STR_PAD_LEFT);
        $second = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        return "#{$first}{$second}";
    }

    /**
     * Imposta le dimensioni del file nel record EcoNFT.
     *
     * @param Teams_item $EGI Record EcoNFT
     * @param string $filePath Percorso del file
     * @return void
     */
    protected function setFileDimensions($EGI, $filePath)
    {
        $dimensions = getimagesize($filePath);
        $EGI->dimension = $dimensions ? "w: {$dimensions[0]} x h: {$dimensions[1]}" : 'empty';
    }

    /**
     * Imposta la dimensione del file nel record EcoNFT.
     *
     * @param Teams_item $EGI Record EcoNFT
     * @param string $filePath Percorso del file
     * @return void
     */
    protected function setFileSize($EGI, $filePath)
    {
        $EGI->size = $this->formatSizeInMegabytes(filesize($filePath));
    }

    /**
     * Imposta le proprietà media del file nel record EcoNFT.
     *
     * @param Teams_item $EGI Record EGI
     * @return void
     */
    protected function setFileMediaProperties($EGI)
    {
        $isImage = $EGI->type === 'image';
        $EGI->media = !$isImage;
        $EGI->key_file = $isImage ? $EGI->id : 0;
    }

    /**
     * Prepara i dettagli del file per l'elaborazione.
     *
     * @param \Illuminate\Http\UploadedFile $file File caricato
     * @return array Dettagli del file
     */
    protected function prepareFileDetails($file)
    {
        $encodedLogParams = json_encode([
            'Class' => 'UploadingFiles',
            'Method' => 'prepareFileDetails',
        ]);
        $channel ='upload';

        try {
            $extension = $file->extension();

            $hash_filename = $file->hashName();
            Log::channel($this->channel)->info($encodedLogParams, [
                'Action' => 'Nome hash del file',
                'hash_filename' => $hash_filename
            ]);

            $mimeType = $file->getMimeType();
            Log::channel($this->channel)->info($encodedLogParams, [
                'Action' => 'Mime type del file',
                'mimeType' => $mimeType
            ]);


            $tempRealPath = $file->getRealPath();
            Log::channel($this->channel)->info($encodedLogParams, [
                'Action' => 'Percorso del file temporaneo',
                'tempRealPath' => $tempRealPath
            ]);

            $fileType = $this->getFileType($extension);
            Log::channel($this->channel)->info($encodedLogParams, [
                'Action' => 'Tipo di file',
                'fileType' => $fileType
            ]);

            $original_filename = $file->getClientOriginalName();
            Log::channel($this->channel)->info($encodedLogParams, [
                'Action' => 'Nome originale del file',
                'original_filename' => $original_filename
            ]);

            $crypt_filename = $this->my_advanced_crypt($original_filename, 'e');
            if ($crypt_filename === false) {
                Log::channel($channel)->error($encodedLogParams. ' Errore durante la crittografia del nome del file');
                throw new CustomException('ERROR_DURING_FILE_NAME_ENCRYPTION');
            }
            Log::channel($channel)->info($encodedLogParams, [
                'Action' => 'Nome criptato del file',
                'crypt_filename' => $crypt_filename
            ]);

            $num = FileHelper::generate_position_number($this->team_id);
            Log::channel($channel)->info($encodedLogParams, [
                'Action' => 'Numero di posizione',
                'num' => $num
            ]);

            $default_name = $this->generateDefaultName($num);
            Log::channel($channel)->info($encodedLogParams, [
                'Action' => 'Nome predefinito',
                'default_name' => $default_name
            ]);

            $destinationPathImage = $this->getImagePath();
            Log::channel($channel)->info($encodedLogParams, [
                'Action' => 'Percorso immagine',
                'destinationPathImage' => $destinationPathImage
            ]);

            // Dobbiamo creare il percorso della cartella di destinazione
            $directory = storage_path($destinationPathImage);
            Log::channel($channel)->info($encodedLogParams, [
                'Action' => 'Percorso della cartella di destinazione',
                'directory' => $directory
            ]);

            return compact('hash_filename', 'extension', 'mimeType', 'fileType', 'tempRealPath', 'crypt_filename', 'num', 'default_name', 'directory', 'destinationPathImage');

        } catch (\Exception $e) {

            Log::channel($channel)->error($encodedLogParams, [
                'Action' => 'Errore durante la preparazione dei dettagli del file',
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Genera il percorso per il salvataggio dell'immagine.
     *
     * @return string Percorso dell'immagine
     */
    protected function getImagePath()
    {
        $root = config('app.bucket_root_file_folder');
        $userId = preg_replace('/[^A-Za-z0-9\-]/', '', $this->user_id);
        $teamId = preg_replace('/[^A-Za-z0-9\-]/', '', Auth::user()->currentTeam->id);
        return $root . '/' . $userId . '/' . $teamId;
    }

    /**
     * Show the user profile screen.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function show()
    {
        // dd(view()->getFinder()->getPaths());
        // dd(config('view.paths'));
        return view('uploadmanager::uploading_files');

    }
}
