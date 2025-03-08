<?php

namespace App\Helpers;

use App\Exceptions\MintingException;
use App\Helpers\ErrorCode;
use App\Interfaces\ExceptionHandlerInterface;

/**
 * Class CalcSingleRoyaltyAmount
 * @param $exceptionHandler
 * @package App\Helpers
 */


class CalcSingleRoyaltyAmount
{
    /**
     * @param float $buyAmount
     * @param float $royalty
     * @param ExceptionHandlerInterface $exceptionHandler
     * @param string $beneficiary
     * @return float
     * Se l'argomento $royalty è un numero compreso tra 0 e 100 viene effettuato il calcolo
     * e restituito il valore di royalty da pagare.
     * Se l'argomento dovesse essere un valore diverso oppure null chiama la gestione degli
     * errori e comunica che l'argomento passato è null, comunica anche il proprietario della
     * royalty con l'argomento $beneficiary
     */

    public static function execute(float $buyAmount, float $royalty, ExceptionHandlerInterface $exceptionHandler, string $beneficiary): float
    {
        if ($royalty >= 0 && $royalty <= 100) {

            return $buyAmount * $royalty / 100;

        } else {
            $exceptionHandler->handleException(
                new MintingException(
                __('errors.minting.error_during_save_the_data'),
               "Nessun valore passato come percentuale durante il calcolo della royalty per $beneficiary" ,
                app('errorDecoder')->decodeNumber(ErrorCode::CALC_OF_AMOUNT_TO_BE_PAID))
            );

            return 0;
        }
    }


}