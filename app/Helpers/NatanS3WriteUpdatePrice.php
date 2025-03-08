<?php

namespace App\Helpers;

use App\Exceptions\MintingException;
use App\Minting\DTOs\DataMint_Blockchain;
use App\Minting\DTOs\DataMintTransaction;
use Illuminate\Support\Facades\Storage;

class NatanS3WriteUpdatePrice
{
    /**
     * Upload a file to S3 storage.
     * @param DataMint_Blockchain $dto
     *
     * @return bool
     */

    public static function execute(DataMint_Blockchain $dto): bool
    {
        $timestamp = now()->format('YmdHis');

        // Prepara il nome del file con il timestamp
        $file_name = 'price_' . $timestamp . '.json';  // Ad esempio, potrebbe diventare "price_20231027123045.json"
        $filePath = $dto->getJsonMetadataFilePath();
        $visibility = $dto->getVisibility();
        $exceptionHandler = $dto->getExceptionHandler();

        try {
            $s3 = Storage::disk('do');
            $fullFilePath = $filePath . '/' . $file_name;

            // Salva il file
            $s3->put($fullFilePath, $dto->getBuyAmount(), $visibility);  // Assumo che getBuyAmount() l'importo di vendita

        } catch (\Exception $e) {
            $exceptionHandler->handleException(
                new MintingException(
                    __('errors.minting.error_during_save_the_data'),
                    'Si sta salvando un nuovo prezzo di vendita nello space: '. $e->getMessage(),
                    ErrorCode::S3_STORE_UPDATE));
            return false;
        }
        return true;
    }

}
