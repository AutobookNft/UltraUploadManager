<?php

namespace Ultra\UploadManager\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use Exception;

class SystemTempFileController extends Controller
{
    protected $channel = 'upload';

    /**
     * Salva un file nella directory temporanea di sistema come fallback quando
     * il metodo standard di salvataggio fallisce.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveToSystemTemp(Request $request)
    {
        // Verifica se è stato caricato un file
        if (!$request->hasFile('file')) {
            Log::channel($this->channel)->error('Classe: SystemTempFileController. Method: saveToSystemTemp. Action: Nessun file caricato');
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        try {
            // Recupera il file dalla richiesta
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();

            Log::channel($this->channel)->info('Classe: SystemTempFileController. Method: saveToSystemTemp. Action: Tentativo di salvataggio fallback', ['filename' => $fileName]);

            // Determina la directory temporanea di sistema
            $systemTempDir = sys_get_temp_dir();

            // Crea una sottodirectory dedicata all'applicazione per evitare conflitti
            $appTempDir = $systemTempDir . DIRECTORY_SEPARATOR . 'ultra_upload_temp';

            // Assicurati che la directory esista
            if (!File::exists($appTempDir)) {
                File::makeDirectory($appTempDir, 0777, true);
                Log::channel($this->channel)->info('Classe: SystemTempFileController. Method: saveToSystemTemp. Action: Creata directory temporanea', ['directory' => $appTempDir]);
            }

            // Genera un nome univoco per evitare sovrascritture
            $uniqueFilename = uniqid() . '_' . $fileName;
            $fullPath = $appTempDir . DIRECTORY_SEPARATOR . $uniqueFilename;

            Log::channel($this->channel)->info('Classe: SystemTempFileController. Method: saveToSystemTemp. Action: Tentativo di salvataggio in', ['path' => $fullPath]);

            // Sposta il file nella directory temporanea
            if ($file->move($appTempDir, $uniqueFilename)) {
                Log::channel($this->channel)->info('Classe: SystemTempFileController. Method: saveToSystemTemp. Action: File salvato con successo', [
                    'fileName' => $fileName,
                    'tempPath' => $fullPath
                ]);

                // Verifica che il file esista effettivamente
                if (!File::exists($fullPath)) {
                    throw new Exception('File moved but not accessible');
                }

                // Imposta permessi corretti
                chmod($fullPath, 0644);

                return response()->json([
                    'message' => 'File uploaded successfully to system temp',
                    'fileName' => $fileName,
                    'tempPath' => $fullPath,
                    'success' => true
                ], 200);
            } else {
                throw new Exception('Failed to move file to temp directory');
            }

        } catch (Exception $e) {
            Log::channel($this->channel)->error('Classe: SystemTempFileController. Method: saveToSystemTemp. Action: Errore nel salvataggio del file', ['error' => $e->getMessage()]);

            // Prova un ultimo tentativo disperato usando file_put_contents
            try {
                $file = $request->file('file');
                $fileName = $file->getClientOriginalName();
                $contents = file_get_contents($file->getRealPath());

                $lastResortPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . '_' . $fileName;

                if (file_put_contents($lastResortPath, $contents)) {
                    Log::channel($this->channel)->info('Classe: SystemTempFileController. Method: saveToSystemTemp. Action: File salvato con metodo estremo', [
                        'fileName' => $fileName,
                        'tempPath' => $lastResortPath
                    ]);

                    return response()->json([
                        'message' => 'File uploaded using last resort method',
                        'fileName' => $fileName,
                        'tempPath' => $lastResortPath,
                        'success' => true
                    ], 200);
                }
            } catch (Exception $lastResortException) {
                Log::channel($this->channel)->error('Classe: SystemTempFileController. Method: saveToSystemTemp. Action: Fallito anche ultimo tentativo', [
                    'error' => $lastResortException->getMessage()
                ]);
            }

            return response()->json([
                'error' => 'Failed to save file to system temp directory',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un file temporaneo dalla directory di sistema.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSystemTempFile(Request $request)
    {
        $tempPath = $request->input('tempPath');

        if (!$tempPath) {
            return response()->json(['error' => 'No temp path provided'], 400);
        }

        try {
            Log::channel($this->channel)->info('Classe: SystemTempFileController. Method: deleteSystemTempFile. Action: Tentativo di eliminazione', ['tempPath' => $tempPath]);

            // Verifica che il file esista
            if (!File::exists($tempPath)) {
                Log::channel($this->channel)->warning('Classe: SystemTempFileController. Method: deleteSystemTempFile. Action: File non trovato', ['tempPath' => $tempPath]);
                return response()->json(['message' => 'File not found, already deleted'], 200);
            }

            // Elimina il file
            if (File::delete($tempPath)) {
                Log::channel($this->channel)->info('Classe: SystemTempFileController. Method: deleteSystemTempFile. Action: File eliminato con successo', ['tempPath' => $tempPath]);
                return response()->json(['message' => 'File deleted successfully'], 200);
            } else {
                throw new Exception('Failed to delete file');
            }

        } catch (Exception $e) {
            Log::channel($this->channel)->error('Classe: SystemTempFileController. Method: deleteSystemTempFile. Action: Errore nell\'eliminazione', [
                'error' => $e->getMessage(),
                'tempPath' => $tempPath
            ]);

            return response()->json([
                'error' => 'Failed to delete temp file',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
