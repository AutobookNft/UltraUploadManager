<?php

namespace Ultra\UploadManager\Controllers;

use Ultra\UploadManager\Events\FileProcessingUpload;

use Ultra\UploadManager\Exceptions\VirusException;
use Ultra\UploadManager\Exceptions\CustomException;
use Ultra\UploadManager\Services\TestingConditionsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;
use Symfony\Component\Process\Process;
use Illuminate\Routing\Controller;
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;
use Ultra\UploadManager\Traits\TestingTrait;

class ScanVirusController extends Controller
{
    use HasValidation, HasUtilitys, TestingTrait;

    protected $user_id;
    protected $current_team;
    protected $path_image;
    protected $floorPrice;
    protected $team_id;
    protected $channel = 'upload';


    public function startVirusScan(Request $request)
    {
        $fileName = $request->input('fileName');
        $index = $request->input('index');
        $customTempPath = $request->input('customTempPath'); // Nuovo parametro per il percorso alternativo

        $channel ='upload';

        $encodedLogParams = json_encode([
            'Class' => 'UploadingFiles',
            'Method' => 'startVirusScan',
        ]);

        Log::channel('upload')->info($encodedLogParams, ['Action' => 'Inizio scansione antivirus per il file:', 'fileName' => $fileName, 'index' => $index]);

        // Convert the 'finished' input from a string to a boolean.
        // This ensures that the value is correctly interpreted as a boolean,
        // as FormData in JavaScript can send boolean values as strings.
        $finished = filter_var($request->input('finished'), FILTER_VALIDATE_BOOLEAN);

        Log::channel('upload')->info($encodedLogParams, ['Action' => 'Finished', 'finished' => $finished]);

        // La variabile someInfectedFiles risulterà maggiore di zero se almeno un file è risultato infetto
        $someInfectedFiles = $request->input('someInfectedFiles');
        Log::channel('upload')->info($encodedLogParams, ['Action' => 'Some Infected Files', 'someInfectedFiles' => $someInfectedFiles]);

        // Determina il percorso del file da scannerizzare
        // Supporta sia il percorso standard che quello alternativo
        if ($customTempPath && file_exists($customTempPath)) {
            // Usa il percorso personalizzato fornito dal client (metodo alternativo)
            $filePath = $customTempPath;
            Log::channel('upload')->info($encodedLogParams, ['Action' => 'Utilizzo percorso file temporaneo alternativo', 'filePath' => $filePath]);
        } else {
            // Usa il percorso standard
            $filePath = storage_path('app/'.config('app.bucket_temp_file_folder') .'/'. $fileName);
        }

        $scanningIsRunning= __('label.antivirus_scan_in_progress');
        FileProcessingUpload::dispatch("$scanningIsRunning: $fileName", 'virusScan', Auth::id(), 0);
        Log::channel('upload')->info($encodedLogParams, ['Action' => 'Inizio scansione antivirus per il file:', 'filePath' => $filePath]);

        //Simula un errore di file temporaneo non trovato
        if (TestingConditionsManager::getInstance()->isTesting('TEMP_FILE_NOT_FOUND') && $index === '0') {
            $filePath .= '1'; // Modifica il percorso per simulare un errore
        }

        // Controllo se il file esiste
        if (!$fileName || !file_exists($filePath)) {
            Log::channel('upload')->error($encodedLogParams, ['Action' => 'File non trovato per la scansione antivirus', 'fileName' => $fileName, 'error' => "$filePath: No such file or directory"]);

            // Verifica se c'è un file caricato direttamente nella richiesta che possiamo usare
            if ($request->hasFile('file')) {
                try {
                    $uploadedFile = $request->file('file');
                    $tempDir = dirname($filePath);

                    // Assicurati che la directory esista
                    if (!file_exists($tempDir)) {
                        mkdir($tempDir, 0777, true);
                    }

                    // Sposta il file al percorso desiderato
                    if ($uploadedFile->move($tempDir, basename($filePath))) {
                        Log::channel('upload')->info($encodedLogParams, ['Action' => 'File salvato proprio prima della scansione', 'filePath' => $filePath]);

                        // Ora il file esiste, possiamo continuare con la scansione
                    } else {
                        // Fallimento anche dopo il tentativo di salvataggio diretto
                        Log::channel('upload')->error($encodedLogParams, ['Action' => 'Impossibile salvare il file prima della scansione', 'fileName' => $fileName]);

                        // Opzione consigliata per non bloccare il flusso: continua senza scansione
                        return response()->json([
                            'state' => 'scanSkipped',
                            'userMessage' => __('label.scan_skipped_but_upload_continues'),
                            'file' => $fileName,
                            'virusFound' => false,
                            'someInfectedFiles' => $someInfectedFiles,
                        ], 200);
                    }
                } catch (\Exception $e) {
                    Log::channel('upload')->error($encodedLogParams, ['Action' => 'Eccezione durante il tentativo di salvataggio diretto', 'error' => $e->getMessage()]);

                    // Continua senza scansione
                    return response()->json([
                        'state' => 'scanSkipped',
                        'userMessage' => __('label.scan_skipped_but_upload_continues'),
                        'file' => $fileName,
                        'virusFound' => false,
                        'someInfectedFiles' => $someInfectedFiles,
                    ], 200);
                }
            } else {
                // Se non c'è un file nella richiesta e non esiste nel percorso specificato, non possiamo procedere con la scansione
                $scanningStopped = __('label.scanning_stopped');
                FileProcessingUpload::dispatch("$scanningStopped: $fileName", 'endVirusScan', Auth::id(), 0);

                // MODIFICA: invece di lanciare un'eccezione che interrompe il flusso,
                // restituisci una risposta che permette di continuare
                return response()->json([
                    'state' => 'scanSkipped',
                    'userMessage' => __('label.scan_skipped_but_upload_continues'),
                    'file' => $fileName,
                    'virusFound' => false,
                    'someInfectedFiles' => $someInfectedFiles,
                ], 200);
            }
        }

        try {
            // Esegui la scansione con ClamAV
            $process = new Process(['clamscan', '--no-summary', '--stdout', $filePath]);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::channel('upload')->error($encodedLogParams, ['Action' => 'Errore durante la scansione antivirus per il file', 'fileName' => $fileName, 'error' => $process->getErrorOutput()]);
                throw new CustomException('SCAN_ERROR');
            }

            if (TestingConditionsManager::getInstance()->isTesting('SCAN_ERROR') && $index === '0') {
                Log::channel('upload')->error($encodedLogParams, ['Action' => 'Simulazione errore server']);
                throw new CustomException('SCAN_ERROR');
            }

            // Risultato della scansione
            $output = $process->getOutput();
            Log::channel($channel)->info($encodedLogParams, ['Action' =>'Risultato della scansione antivirus per il file:', 'fileName' => $fileName, 'output' => $output]);

            // ROUTINE DI TEST per simulare un file infetto
            if (TestingConditionsManager::getInstance()->isTesting('VIRUS_FOUND') && $index === '0') {
                Log::channel($channel)->error($encodedLogParams, ['Action' => 'SIMULAZIONE DI Errore virus_found', 'fileName' => $fileName, 'error' => $process->getErrorOutput(), 'Index'=>$index]);
                throw new CustomException('VIRUS_FOUND');
            }

            if ($finished) {

                // Ultimo file da scansionare (potrebbe anche essere l'unico)
                if ($someInfectedFiles) {

                    // Se c'è stato almeno un file infetto e siamo alla fine del processo di scansione
                    $message = __("label.one_or_more_files_were_found_infected");

                    // Comunico il messaggio ai piedi della pagina
                    FileProcessingUpload::dispatch($message, 'allFileScannedSomeInfected', Auth::id(), 0);

                    // Loggo il messaggio
                    Log::channel('upload')->warning($encodedLogParams, ['Action' => 'One or more files were found infected', ['fileName' => $fileName]]);

                    // Verifico se è il file corrente ad essere infetto
                    // Nel caso il file contenga un virus, l'output di clamscan può essere una cosa del genere: Virus FOUND in file abc.txt
                    // Quindi se la variabile $output contiene la parola FOUND (diverso da false), il file è infetto
                    if (strpos($output, 'FOUND') !== false){
                        // Il file risulta infetto
                        throw new CustomException('VIRUS_FOUND');
                    }else{
                        // Il messaggio deve specificare che ci sono stati dei file infetti, ma non quello corrente
                        $responseCode=200;
                        $message = __("label.one_or_more_files_were_found_infected");
                        FileProcessingUpload::dispatch($message, 'allFileScannedNotInfected', Auth::id(), 0);
                        Log::channel('upload')->info($encodedLogParams, ['Action' => 'All files are scanned and one or more where found infeted', ['fileName' => $fileName]]);
                        return response()->json([
                            'userMessage' => $message,
                            'state' => 'allFileScannedSomeInfected',
                            'file' => $fileName,
                            'virusFound' => false,
                            'someInfectedFiles' => $someInfectedFiles,
                        ], $responseCode);
                    }

                }else{

                    // Se nessun file è risultato infetto e siamo alla fine del processo di scansione
                    $responseCode=200;
                    $message = __("label.all_files_were_scanned_no_infected_files");
                    FileProcessingUpload::dispatch($message, 'allFileScannedNotInfected', Auth::id(), 0);
                    Log::channel('upload')->info($encodedLogParams, ['Action' => 'All files are scanned and no infected files were found', ['fileName' => $fileName]]);
                    return response()->json([
                        'state' => 'allFileScannedNotInfected',
                        'userMessage' => $message,
                        'file' => $fileName,
                        'virusFound' => false,
                        'someInfectedFiles' => $someInfectedFiles,
                    ], $responseCode);
                }

            }else{

                // Non siamo alla fine del processo di scansione
                // if ((strpos($output, 'OK') !== false) && ($index === 0 || $index === 2 || $index === 4)) { // SOLO PER DEBUG: simulo un file infetto

                if ((strpos($output, 'FOUND') !== false)) {

                    // Nel caso il file contenga un virus, l'output di clamscan può essere una cosa del genere: Virus FOUND in file abc.txt
                    // Quindi se la variabile $output contiene la parola FOUND (diverso da false), il file è infetto
                    $message = __("label.the_uploaded_file_was_detected_as_infected");
                    $statusScan = 'infected';
                    Log::channel('upload')->warning($encodedLogParams, ['Action' => 'Il file caricato è stato rilevato come infetto', ['fileName' => $fileName]]);
                    FileProcessingUpload::dispatch($message, $statusScan, Auth::id(), 0);

                    throw new CustomException('VIRUS_FOUND');

                }else {

                    // Se la stringa dell'output NON contiene la parola FOUND significa che il file è pulito, si procede con la scansione del file successivo
                    $statusScan = 'virusScan';
                    $message = __("label.file_scanned_successfully");
                    $responseCode=200;
                    Log::channel('upload')->info($encodedLogParams, ['Action' => 'Scansione completata con successo per il file', ['fileName' => $fileName]]);
                    FileProcessingUpload::dispatch($message, $statusScan, Auth::id(), 0);
                    return response()->json([
                        'state' => $statusScan,
                        'userMessage' => __("label.file_scanned_successfully", ['fileCaricato' => $fileName]),
                        'file' => $fileName,
                        'virusFound' => false,
                        'someInfectedFiles' => $someInfectedFiles,
                    ], $responseCode);

                }
            }

        } catch (VirusException $e) {

            $response =   response()->json([
                'userMessage' => $e->getMessage(),
                'statusScan' => $e->getStatusScan(),
                'codeError' => $e->getVirusFoundCode(),
                'fileCaricato' => $fileName,
                'virusFound' => $e->getVirusFound(),
                'someInfectedFiles' => $someInfectedFiles,
            ], $e->getResponseCode());

            Log::channel('upload')->error($encodedLogParams, ['Action' => 'Errore durante la scansione del file', ['fileName' => $fileName, 'error' => $e->getMessage()]]);
            return $response;

        } catch (CustomException $e) {
            // Gestisci eccezione personalizzata in modo più flessibile
            if ($e->getCode() === 'FILE_NOT_FOUND' || $e->getCode() === 'SCAN_ERROR') {
                // Per questi errori, invece di bloccare, continuiamo il flusso
                Log::channel('upload')->warning($encodedLogParams, ['Action' => 'Errore gestito, ma continuiamo il flusso', 'error' => $e->getMessage()]);

                return response()->json([
                    'state' => 'scanSkipped',
                    'userMessage' => __('label.scan_skipped_but_upload_continues'),
                    'file' => $fileName,
                    'virusFound' => false,
                    'someInfectedFiles' => $someInfectedFiles,
                ], 200);
            }

            // Rilancia le altre eccezioni
            throw $e;
        }
    }


    }
