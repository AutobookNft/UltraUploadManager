<?php

namespace App\Helpers;

/**
 * @param $errorString
 * @return bool|int|string
 *
 *  0 => "Errore generico",
 *  Il primo numero indica il metodo Padre in cui è avvenuto l'errore
 *  Il secondo numero indica il metodo figlio in cui è avvenuto l'errore
 *  1 = MintingClass
 *  2 = CreateTokenEcoNFTClass
 *  3 = MintingTransactionClass
 *  4 = CalcSingleRoyaltyAmount
 *  5 = HasSufficientBalance
 *  Esempio: 21.
 *      2 = generateTokenEcoNFT
 *      21 = extractDataEcoNFT
 */
class ErrorCode
{
    const MAX_FILE_SIZE = 903;
    const MIME_TYPE_NOT_ALLOWED = 904;
    const INVALID_IMAGE_STRUCTURE = 905;
    const INVALID_FILE_NAME = 906;
    const ERROR_GETTING_PRESIGNED_URL = 907;
    const ERROR_DURING_FILE_UPLOAD = 908;
    const ERROR_DELETING_FILE = 910;

    const CALC_OF_AMOUNT_TO_BE_PAID = 41;
    const INSUFFICIENT_BALANCE = 51;
    const NULL_BALANCE = 52;
    const NULL_ECONFT_AMOUNT = 53;
    const SELL_TO_BUYER = 32;
    const UPDATE_BALANCE = 33;
    const EXTRACT_DATA_ECO_NFT = 21;
    const EXTRACT_DATA_TRAITS = 22;
    const CONVERT_METADATA_IN_JSON = 23;
    const SAVE_JSON_METADATA_IN_DB = 24;
    const HAS_INSUFFICIENT_BALANCE = 15;
    const BUYER_TRANSACTION = 16;
    const TRANSACTION = 17;
    const PAY_TO_SELLERS = 18;
    const PAY_TO_NATAN = 19;
    const MISSING_RECEIVING_TRANSACTION = 001;
    const ROYALTY_SET_TO_ZERO = 002;
    const PAY_TO_EPP = 110;
    const TRANSFER_ECO_NFT = 111;
    const WRITE_TRANSACTION_ON_BLOCKCHAINS = 112;
    const WRITE_TOKEN_IN_TEAM_ITEMS = 113;
    const S3_STORE_UPDATE = 114;
    const S3_STORE_DELETE = 115;
    const WALLET_ADDRESS_NOT_FUND = 116;

    const ERROR_GENERIC = 0;
}
