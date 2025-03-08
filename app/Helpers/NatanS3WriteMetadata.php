<?php

namespace App\Helpers;

use App\Exceptions\MintingException;
use App\Minting\DTOs\DataMint_Blockchain;
use Illuminate\Support\Facades\Storage;

class NatanS3WriteMetadata
{
    /**
     * Upload a file to S3 storage.
     * @param DataMint_Blockchain $dto
     *
     * @return bool
     */

    public static function execute(DataMint_Blockchain $dto): bool
    {
        $file_name = 'metadata.json';
        $filePath = $dto->getJsonMetadataFilePath();
        $visibility = $dto->getVisibility();
        $exceptionHandler = $dto->getExceptionHandler();

        try {
            $s3 = Storage::disk('do');
            $fullFilePath = $filePath . '/' . $file_name;

            // Supponiamo che $jsonString contenga la tua stringa JSON
            $jsonString = $dto->getJsonMetadata();
            $directory = storage_path('app/public/image');
            // Percorso dove vuoi salvare il file
            $localFilePath = $directory. '/metadata.json';

            // Scrive la stringa JSON nel file
            $result = file_put_contents($localFilePath, $jsonString);
            $localData = file_get_contents($localFilePath);

            $s3->put($fullFilePath, $localData, $visibility);

        } catch (\Exception $e) {
            $exceptionHandler->handleException(
                new MintingException(
                __('errors.minting.error_during_save_the_data'),
                'Si sta salvando file dei metadata nello space'.$e->getMessage(),
                ErrorCode::S3_STORE_UPDATE));
                return false;
        }
        return true;
    }

    /**
     * Delete a file from S3 storage.
     *
     * @param string $path Path of the file to be deleted
     * @return bool True if successful, otherwise false
     * @throws \Exception
     */
    protected function deleteFile(string $path): bool
    {
        try {
            $s3 = Storage::disk('do');
            return $s3->delete($path);

        } catch (\Exception $e) {
            $this->exceptionHandler->handleException(
                new MintingException(__('errors.minting.error_during_save_the_data'),
                    $e->getMessage(),
                    app('errorDecoder')->decodeNumber(ErrorCode::S3_STORE_DELETE))
            );
            return false;
        }
    }
}
