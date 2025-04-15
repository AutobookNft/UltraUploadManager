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


class DeleteTempFolder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tmpFolder;

    public function __construct($tmpFolder)
    {
        $this->tmpFolder = $tmpFolder;
    }

    public function handle()
    {

        $folderName = $this->tmpFolder;

        if (empty($folderName) || $folderName === '/') {
            Log::channel('upload')->error('Classe: UploadingFiles. Method: deleteTemporaryFolder. Action: Tentativo di eliminazione di una cartella non valida o alla radice.');
            return response()->json(['error' => 'Invalid folder path. Deletion aborted.'], 400);
        }


        Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFolder. Action: Tentativo di eliminazione della cartella temporanea:', ['folderName' => $folderName]);

        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('app.do_default_region'),
            'endpoint' => config('app.do_endpoint'),
            'credentials' => [
                'key'    => config('app.do_access_key_id'),
                'secret' => config('app.do_secret_access_key'),
            ],
        ]);

        $bucket = config('app.do_bucket');

        try {
            $results = $s3Client->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $folderName,
            ]);

            if (isset($results['Contents'])) {
                foreach ($results['Contents'] as $object) {
                    $s3Client->deleteObject([
                        'Bucket' => $bucket,
                        'Key'    => $object['Key'],
                    ]);
                }
            }

                // Verifica se la cartella è vuota
            $remainingObjects = $s3Client->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $folderName,
            ]);


            Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFolder. Action: $remainingObjects[\'Contents\']'. json_encode($remainingObjects['Contents']));

            if (!isset($remainingObjects['Contents']) || count($remainingObjects['Contents']) === 0) {
                Log::channel('upload')->info('Classe: UploadingFiles. Method: deleteTemporaryFolder. Action: Temporary folder deleted successfully');
                return response()->json([
                    'userMessage' => 'Temporary folder deleted successfully',
                    'devMessage' => 'Temporary folder deleted successfully',
                    'state' => 'success',
                    'file' => $folderName,
                    'someObjectsNotDeleted' => false,
                ], 200);
            } else {
                Log::channel('upload')->error('Classe: UploadingFiles. Method: deleteTemporaryFolder. Action: Error, some objects could not be deleted.');
                return response()->json(['error' => 'Could not delete all objects in the temporary folder.'], 500);
            }

        } catch (AwsException $e) {

            Log::error('Classe: UploadingFiles. Method: deleteTemporaryFolder. Action: Error deleting temporary folder: ' . $e->getMessage());
            return response()->json(['error' => 'Could not delete temporary folder.'], 500);

        }


    }

}

