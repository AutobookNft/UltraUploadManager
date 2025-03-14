<?php

namespace Ultra\UploadManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Notifications\UploadStatusNotification;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;


class SaveFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileToSave;
    protected $tempFilePath;
    protected $fileDetails;
    protected $egiRecord;
    protected $userId;
    protected $nomeFile;


    public function __construct($fileToSave, $tempFilePath, $fileDetails, $egiRecord, $userId, $nomeFile)
    {
        $this->fileToSave = $fileToSave;
        $this->tempFilePath = $tempFilePath;
        $this->fileDetails = $fileDetails;
        $this->egiRecord = $egiRecord;
        $this->userId = $userId;
        $this->nomeFile = $nomeFile;
    }

    public function handle()
    {
        Log::channel('upload')->info('Classe: UploadingFiles. Method: handle. Action: Path temporaneo', ['temp' => $this->tempFilePath]);
        Log::channel('upload')->info('Classe: UploadingFiles. Method: handle. Action: File da salvare', ['file' => $this->fileToSave]);

        try {
            // Legge il contenuto del file temporaneo
            $contents = file_get_contents($this->tempFilePath);
            Log::channel('upload')->info('Classe: UploadingFiles. Method: handle. Action: Contenuto del file letto correttamente', ['file' => $this->fileToSave]);

            // Salva il file nel disco configurato come 'do' (DigitalOcean Spaces)
            $path_do = Storage::disk('do')->put($this->fileToSave, $contents, 'public') ?? '';
            Log::channel('upload')->info('Classe: UploadingFiles. Method: handle. Action: File salvato correttamente', ['file' => $this->fileToSave]);

            $path_public = Storage::disk('public')->put($this->fileToSave, $contents, 'public') ?? '';
            Log::channel('upload')->info('Classe: UploadingFiles. Method: handle. Action: File salvato correttamente', ['file' => $this->fileToSave]);

            // Notifica l'utente del completamento dell'upload
            $user = User::find($this->userId);
            $user->notify(new UploadStatusNotification('File uploaded successfully'));

        } catch (\Exception $e) {
            Log::channel('upload')->error('Classe: UploadingFiles. Method: handle. Action: Errore durante il caricamento del file', ['error' => $e->getMessage()]);
            throw $e;
        }


        // Metodo per eliminare il file usando l'SDK AWS
        $fileTodelete = env('BUCKET_TMP_FILE_FOLDER').'/'.$this->nomeFile;
        $this->deleteTemporaryFile($fileTodelete);

    }

    public function deleteTemporaryFile($filePath)
    {

        Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFile. Action: Tentativo di eliminazione del file:', ['path' => $filePath]);

        if (Storage::disk('do')->exists($filePath)) {
            Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFile. Action: Il file esiste, tentando di eliminarlo.');

            try {
                $s3Client = new S3Client([
                    'version' => 'latest',
                    'region'  => env('DO_DEFAULT_REGION'),
                    'endpoint' => env('DO_ENDPOINT'),
                    'credentials' => [
                        'key'    => env('DO_ACCESS_KEY_ID'),
                        'secret' => env('DO_SECRET_ACCESS_KEY'),
                    ],
                ]);

                $bucket = env('DO_BUCKET');
                Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFile. Action: Bucket:', ['bucket' => $bucket]);
                Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFile. Action: Key:', ['key' => $filePath]);

                $result = $s3Client->deleteObject([
                    'Bucket' => $bucket,
                    'Key'    => $filePath, // Assicurati che questo sia un percorso relativo
                ]);

                // Logga l'intero risultato per vedere tutti i dettagli
                Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFile. Action: Risultato dell\'eliminazione:', ['result' => $result->toArray()]);

            } catch (AwsException $e) {
                Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFile. Action: Error during file deletion: ' . $e->getMessage());
                return response()->json(['error' => 'Could not delete file.'], 500);
            }
        } else {
            Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFile. Action: Il file non esiste.');
            return response()->json(['error' => 'Il file non esiste.'], 404);
        }
    }

}

