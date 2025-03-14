<?php

namespace Ultra\UploadManager\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use Exception;
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;
use Ultra\UploadManager\Traits\TestingTrait;


class SaveTemporaryFiles extends Controller
{

    use HasValidation, HasUtilitys, TestingTrait;

    protected $channel = 'upload';

    /**
     * Salva un file temporaneo con gestione robusta dei permessi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTemporaryFile(Request $request)
    {
        // Verifica se è stato caricato un file
        if (!$request->hasFile('file')) {
            Log:: channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Nessun file caricato');
            return response()->json(['error' => 'No file uploaded'], 404);
        }

        try {

            // Recupera il file da request
            $file = $request->file('file') ?? null;

            // Recupera il nome del file
            $fileName = $file->getClientOriginalName();
            Log::channel($this->channel)->info('Classe: UploadinFiles. Method: saveTemporaryFile. Action: File da salvare nella cartella temp', ['filename' =>  $fileName]);

            // Validazione del file
            $this->validateFile($file);

            // Configurazione della cartella temporanea
            $bucketFolderTemp = config('app.bucket_temp_file_folder');
            $fullPath = storage_path('app/' . $bucketFolderTemp);
            Log::channel($this->channel)->info('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Percorso temporaneo', ['storage temp path' => $fullPath]);

            // Verifica se la directory esiste, altrimenti prova a crearla
            if (!file_exists($fullPath)) {
                $this->createDirectory($fullPath);
            }

            // Tenta di salvare il file
            $storedFilePath = storage_path('app/private/' . $bucketFolderTemp . '/' . $fileName);
            Log::channel($this->channel)->info('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Salvataggio del file', ['storedFilePath' => $storedFilePath]);

            try {
                $file->storeAs($bucketFolderTemp, $fileName);
                Log::channel($this->channel)->info('Classe: UploadinFiles. Method: saveTemporaryFile. Action: File salvato con successo', ['fileName' => $fileName]);

                // Verifica che il file sia stato creato correttamente e sia accessibile
                if (!file_exists($storedFilePath)) {
                    Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Il file non è stato salvato correttamente', ['storedFilePath' => $storedFilePath]);
                    throw new Exception('File could not be saved.');
                }

                Log::channel($this->channel)->info('Classe: UploadinFiles. Method: saveTemporaryFile. Action: File salvato temporaneamente con successo', ['fileName' => $fileName]);

                return response()->json([
                    'message' => 'File uploaded successfully',
                    'fileName' => $fileName,
                    'bucketFolderTemp' => $fullPath
                ], 200);

            } catch (Exception $e) {
                Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Errore nel salvataggio del file. Tentativo di cambiare permessi e riprovare', ['error' => $e->getMessage()]);

                // Tentativo di cambiare permessi della directory
                if ($this->changePermissions($fullPath, 'directory')) {
                    // Riprovare a salvare il file dopo aver cambiato i permessi
                    try {
                        $file->storeAs($bucketFolderTemp, $fileName);
                        Log::channel($this->channel)->info('Classe: UploadinFiles. Method: saveTemporaryFile. Action: File salvato con successo dopo aver cambiato i permessi della directory', ['fileName' => $fileName]);

                        // Verifica che il file sia stato creato correttamente e sia accessibile
                        if (!file_exists($storedFilePath)) {
                            Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Il file non è stato salvato correttamente dopo il retry', ['storedFilePath' => $storedFilePath]);
                            throw new Exception('File could not be saved after permission change.');
                        }

                        return response()->json([
                            'message' => 'File uploaded successfully after permission change',
                            'fileName' => $fileName,
                            'bucketFolderTemp' => $fullPath
                        ], 200);

                    } catch (Exception $ex) {
                        Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Errore nel salvataggio del file dopo aver cambiato i permessi della directory', ['error' => $ex->getMessage()]);

                        // Prova a eliminare e ricreare la directory
                        try {
                            $this->handleDirectoryError($fullPath);
                            // Riprovare a salvare il file dopo aver ricreato la directory
                            $file->storeAs($bucketFolderTemp, $fileName);
                            Log::channel($this->channel)->info('Classe: UploadinFiles. Method: saveTemporaryFile. Action: File salvato con successo dopo aver ricreato la directory', ['fileName' => $fileName]);

                            // Verifica che il file sia stato creato correttamente e sia accessibile
                            if (!file_exists($storedFilePath)) {
                                Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Il file non è stato salvato correttamente dopo aver ricreato la directory', ['storedFilePath' => $storedFilePath]);
                                throw new Exception('File could not be saved after recreating the directory.');
                            }

                            return response()->json([
                                'message' => 'File uploaded successfully after recreating the directory',
                                'fileName' => $fileName,
                                'bucketFolderTemp' => $fullPath
                            ], 200);

                        } catch (Exception $retryEx) {
                            Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Errore nel salvataggio del file dopo aver ricreato la directory', ['error' => $retryEx->getMessage()]);
                            throw new Exception('Impossibile salvare il file dopo aver ricreato la directory.');
                        }
                    }
                } else {
                    // Se non si riesce a cambiare i permessi, prova a eliminare e ricreare la directory
                    try {
                        $this->handleDirectoryError($fullPath);
                        // Riprovare a salvare il file dopo aver ricreato la directory
                        $file->storeAs($bucketFolderTemp, $fileName);
                        Log::channel($this->channel)->info('Classe: UploadinFiles. Method: saveTemporaryFile. Action: File salvato con successo dopo aver ricreato la directory', ['fileName' => $fileName]);

                        // Verifica che il file sia stato creato correttamente e sia accessibile
                        if (!file_exists($storedFilePath)) {
                            Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Il file non è stato salvato correttamente dopo aver ricreato la directory', ['storedFilePath' => $storedFilePath]);
                            throw new Exception('File could not be saved after recreating the directory.');
                        }

                        return response()->json([
                            'message' => 'File uploaded successfully after recreating the directory',
                            'fileName' => $fileName,
                            'bucketFolderTemp' => $fullPath
                        ], 200);

                    } catch (Exception $retryEx) {
                        Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Action: Errore nel salvataggio del file dopo aver ricreato la directory', ['error' => $retryEx->getMessage()]);
                        throw new Exception('Impossibile salvare il file dopo aver ricreato la directory.');
                    }
                }
            }

        } catch (Exception $e) {
            Log::channel($this->channel)->error('Classe: UploadinFiles. Method: saveTemporaryFile. Error uploading file:', ['error' => $e->getMessage()]);
            // Rilancia l'eccezione per gestire tramite ErrorDispatcher in app/Exceptions/Handler.php
            throw $e;
        }
    }
}

