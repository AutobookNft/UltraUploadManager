<?php


namespace App\Helpers;


class ErrorDecodeNumber
{
    /** IMPORTANTE
     * Vedi la decodifica degli errori in App\Helpers\ErrorCode.php
     *
     *
     * */

    // L'istanza Singleton
    private static $instance;

    // Impedisci la creazione di nuove istanze
    private function __construct() {}

    // Ottieni l'istanza Singleton
    public static function getInstance()
    {
        if (null === static::$instance) static::$instance = new static();

        return static::$instance;
    }

    public function decodeNumber(int $errorCode): bool|int
    {
        return match ($errorCode) {
            ErrorCode::MAX_FILE_SIZE => "maxFileSize",
            ErrorCode::MIME_TYPE_NOT_ALLOWED => "mimeTypeNotAllowed",
            ErrorCode::INVALID_IMAGE_STRUCTURE => "invalidImageStructure",
            ErrorCode::INVALID_FILE_NAME => "invalidFileName",
            ErrorCode::ERROR_GETTING_PRESIGNED_URL => "errorGettingPresignedURL",
            ErrorCode::ERROR_DURING_FILE_UPLOAD => "errorDuringFileUpload",
            ErrorCode::EXTRACT_DATA_ECO_NFT => "extractDataEcoNFT",
            ErrorCode::EXTRACT_DATA_TRAITS => "extractDataTraits",
            ErrorCode::CONVERT_METADATA_IN_JSON => "convertMetadataInJson",
            ErrorCode::SAVE_JSON_METADATA_IN_DB => "saveJsonMetadataInDB",
            ErrorCode::BUYER_TRANSACTION => "buyer_transaction",
            ErrorCode::TRANSACTION => "transaction",
            ErrorCode::PAY_TO_SELLERS => "payToSellers",
            ErrorCode::PAY_TO_NATAN => "payToNatan",
            ErrorCode::PAY_TO_EPP => "payToEpp",
            ErrorCode::TRANSFER_ECO_NFT => "transferEcoNft",
            ErrorCode::WRITE_TRANSACTION_ON_BLOCKCHAINS => "writeTransactionOnBlockchains",
            ErrorCode::WRITE_TOKEN_IN_TEAM_ITEMS => "writeTokenInTeamItems",
            ErrorCode::S3_STORE_UPDATE => "s3Store",
            ErrorCode::S3_STORE_DELETE => "s3StoreDelete",
            ErrorCode::WALLET_ADDRESS_NOT_FUND => "walletAddress",
            ErrorCode::INSUFFICIENT_BALANCE => "hasSufficientBalance",
            ErrorCode::SELL_TO_BUYER => "sellToBuyer",
            ErrorCode::UPDATE_BALANCE => "updateBalance",
            ErrorCode::CALC_OF_AMOUNT_TO_BE_PAID => "calcOfAmountToBePaid",
            default => 0,
        };
    }

}
