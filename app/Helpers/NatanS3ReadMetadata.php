<?php

namespace App\Helpers;

use App\Exceptions\MintingException;
use App\Minting\DTOs\DataMint_Blockchain;
use App\Minting\GenerateToken\saveJsonMetadataInDB;
use App\Services\MintingExceptionHandler;
use Illuminate\Support\Facades\Storage;

class NatanS3ReadMetadata
{
    /**
     * Upload a file to S3 storage.
     * @param $filePath
     *
     * @return bool | string
     */

    public static function execute($filePath): bool | array
    {

        // Creo l'oggetto per le eccezioni personalizzate
        $exceptionHandler = new MintingExceptionHandler();

        try {
            $s3 = Storage::disk('do');
            $fullFilePath = $filePath . '/metadata.json';

            if ($s3->exists($fullFilePath)) {
                $contents = $s3->get($fullFilePath);
                return json_decode($contents, true);
            } else {
                // Gestisci il caso in cui il file non esiste
                return false;
            }

        } catch (\Exception $e) {
            $exceptionHandler->handleException(
                new MintingException(
                    __('errors.minting.error_during_save_the_data'),
                    'Si leggendo il file metadata dallo space: '. $e->getMessage(),
                    ErrorCode::S3_STORE_UPDATE));
            return false;
        }
    }

}
