<?php

namespace App\Helpers;

use App\Exceptions\MintingException;
use App\Minting\DTOs\DataMint_Blockchain;
use Illuminate\Support\Facades\Storage;

class NatanS3DeleteMetadata
{

    /**
     * Delete a file from S3 storage.
     *
     * @param string $path Path of the file to be deleted
     * @return bool True if successful, otherwise false
     * @throws \Exception
     */
    protected function deleteFile(DataMint_Blockchain $dto, string $path): bool
    {

        $exceptionHandler = $dto->getExceptionHandler();

        try {
            $s3 = Storage::disk('do');
            return $s3->delete($path);

        } catch (\Exception $e) {
            $exceptionHandler->handleException(
                new MintingException(__('errors.minting.error_during_save_the_data'),
                    $e->getMessage(),
                    app('errorDecoder')->decodeNumber(ErrorCode::S3_STORE_DELETE))
            );
            return false;
        }
    }
}
