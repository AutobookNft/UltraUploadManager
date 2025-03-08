<?php

namespace App\Helpers;

use App\Exceptions\MintingException;
use App\Minting\DTOs\DataMint_Blockchain;
use App\Services\MintingExceptionHandler;
use Illuminate\Support\Facades\Storage;

class NatanS3ReadLatestPrice
{
    /**
     * Upload a file to S3 storage.
     * @param $filePath
     *
     * @return bool | string | null
     */

    public static function execute($filePath): bool|string|null
    {

        // Creo l'oggetto per le eccezioni personalizzate
        $exceptionHandler = new MintingExceptionHandler();

        try {
            $s3 = Storage::disk('do');
            $files = $s3->files($filePath);

            // Filtra solo i file che iniziano con "price_"
            $priceFiles = array_filter($files, function ($file) {
                return str_contains($file, 'price_');
            });

            if (empty($priceFiles)) {
                // Gestisci il caso in cui non ci sono file di prezzo
                return false;
            }

            // Ordina i file per nome in ordine decrescente per ottenere il più recente
            rsort($priceFiles);

            $latestPriceFile = $priceFiles[0];
            $lastPrice = $s3->get($latestPriceFile) ?? 0.00;
            return number_format($lastPrice, 2, '.', ',');

        } catch (\Exception $e) {
            $exceptionHandler->handleException(
                new MintingException(
                    __('errors.minting.error_during_save_the_data'),
                    'Si sta leggendo un nuovo prezzo di vendita nello space: '. $e->getMessage(),
                    ErrorCode::S3_STORE_UPDATE));
            return false;
        }

    }

}
