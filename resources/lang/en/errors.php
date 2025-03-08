<?php

/*
|--------------------------------------------------------------------------
| Traduzione in inglese di tutti i messaggi di errore
|--------------------------------------------------------------------------
|
 */

return [
    'AUTHENTICATION_ERROR' => 'Unauthorized access',
    'SCAN_ERROR' => 'Scan error',
    'VIRUS_FOUND' => 'Virus found',
    'INVALID_FILE_EXTENSION' => 'Invalid file extension',
    'MAX_FILE_SIZE' => 'The file cannot exceed :max byte.',
    'INVALID_FILE_PDF' => 'Invalid PDF file',
    'MIME_TYPE_NOT_ALLOWED' => 'File type not allowed.',
    'INVALID_IMAGE_STRUCTURE' => 'Invalid image structure',
    'INVALID_FILE_NAME' => 'Invalid file name',
    'ERROR_GETTING_PRESIGNED_URL' => 'Error getting presigned URL',
    'ERROR_DURING_FILE_UPLOAD' => 'Error during file upload',
    'UNABLE_TO_SAVE_BOT_FILE' => 'Impossible save file.',
    'UNABLE_TO_CREATE_DIRECTORY' => 'Unable to create folder',
    'UNABLE_TO_CHANGE_PERMISSIONS' => 'Unable to change folder permissions',
    'IMPOSSIBLE_SAVE_FILE' => 'Impossible save file',
    'ERROR_DURING_CREATE_EGI_RECORD' => 'Internal problem, support has already been alerted',
    'ERROR_DURING_FILE_NAME_ENCRYPTION' => 'Error during file name encryption',
    'IMAGICK_NOT_AVAILABLE' => 'Internal problem, support has already been alerted',
    'JSON_ERROR_IN_DISPATCHER' => 'JSON error in dispatcher',
    'GENERIC_SERVER_ERROR' => 'Generic server error, technical team has been informed',
    'FILE_NOT_FOUND' => 'File not found',
    'UNEXPECTED_ERROR' => 'Generic server error, technical team has been informed',
    'ERROR_DELETING_LOCAL_TEMP_FILE' => 'Error deleting local temporary file',

    'scan_error' => 'Scan error',
    'virus_found' => 'Virus found',
    'required' => 'The field is required.',
    'file' => 'An error occurred while uploading the file.',
    'file_extension_not_valid' => 'File extension not valid',
    'mimes' => 'The file must be of type: :values.',
    'max_file_size' => 'The file cannot exceed :max byte.',
    'invalid_pdf_file' => 'Invalid PDF file',
    'mime_type_not_allowed' => 'File type not allowed.',
    'invalid_image_structure' => 'Invalid image structure',
    'invalid_file_name' =>  'Invalid file name',
    'error_getting_presigned_URL' => 'Error getting presigned URL',
    'error_getting_presigned_URL_for_user' => 'Error getting presigned URL for user',
    'error_during_file_upload' => 'Error during file upload',
    'error_deleting_file' => 'Error deleting file',
    'upload_finished' => 'Upload finished',
    'some_errors' => 'some errors',
    'upload_failed' => 'upload failed',
    'error_creating_folder' => 'Error creating folder',
    'error_changing_folder_permissions' => 'Error changing folder permissions',
    'local_save_failed_file_saved_to_external_disk_only' => 'Local save failed, file saved to external disk only',
    'external_save_failed_file_saved_to_local_disk_only' => 'External save failed, file saved to local disk only',
    'file_scanning_may_take_a_long_time_for_each_file' => 'File scanning may take a long time for each file',
    'all_files_are_saved' => 'All files are saved',
    'loading_finished_you_can_proceed_with_saving' => 'Loading finished, you can proceed with saving',
    'loading_finished_you_can_proceed_with_saving_and_scan' => 'Loading finished, you can proceed with saving and scan',
    'im_uploading_the_file' => 'I\'m uploading the file',


    'exception' => [
        'NotAllowedTermException' => 'Term not allowed',
        'MissingCategory' => 'You must enter a category.',
        'DatabaseException' => 'A database error occurred',
        'ValidationException' => 'A validation error occurred',
        'HttpException' => 'An HTTP error occurred',
        'ModelNotFoundException' => 'Model not found',
        'QueryException' => 'Query error',
        'MintingException' => 'Error during minting',
        'FileNotFoundException' => 'File not found',
        'InvalidArgumentException' => 'Invalid argument',
        'UnexpectedValueException' => 'Unexpected value',
        'ItemNotFoundException' => 'Item not found',
        'MultipleItemsFoundException' => 'Multiple items found',
        'LogicException' => 'Logic exception',
        'EntryNotFoundException' => 'Entry not found',
        'RuntimeException' => 'Runtime error',
        'BadMethodCallException' => 'Invalid method call',
        'LockTimeoutException' => 'Lock timeout',
        'InvalidIntervalException' => 'Invalid interval',
        'InvalidPeriodParameterException' => 'Invalid period parameter',
        'EndLessPeriodException' => 'Endless period',
        'UnreachableException' => 'Unreachable exception',
        'InvalidTimeZoneException' => 'Invalid time zone',
        'ImmutableException' => 'Immutable exception',
        'InvalidFormatException' => 'Invalid format',
    ],
    'forbidden_term_warning' => "
        <div style=\"text-align: left;\">
            <p>Dear User,</p>
            </br>
            <p>The text you entered violates our community guidelines and norms. Please modify the content and try again.</p>
            </br>
            <p>If you do not understand why this term is prohibited, please refer to the clauses of the agreement you accepted at the time of registration.
            <p>We appreciate your understanding and cooperation.</p>
            </br>
            <p>Best regards,
            <br>
            The Frangette Team</p>
        </div>",

    'letter_of_the_rules_of_conduct' =>
        '<a href=\":link\" style=\"color: blue; text-decoration: underline;\">
            See the community rules page.
        </a>.',

    'forbiddenTermChecker_was_not_initialized_correctly' => 'ForbiddenTermChecker was not initialized correctly',
    'table_not_exist' => 'The table does not exist',
    'unique' => 'This value is already present in your traits library.',
    'the_category_name_cannot_be_empty' => 'The category name cannot be empty',
    'nathing_to_save' => 'Nothing to save',
    'an_error_occurred' => 'Oops! Sorry, an error occurred!',
    'error_number' => 'Error number:',
    'reason' => [
        'reason' => 'reason',
        'wallet_not_valid' => 'Wallet not valid',
        'something_went_wrong' => 'Something went wrong',
    ],
    'solution' => [
        'solution' => 'solution',
        'create_a_new_wallet_and_try_again' => 'Create a new wallet and try again',
        'we_are_already_working_on_solving_the_problem' => 'We are already working on solving the problem',
    ],
    'min' => [
        'string' => 'The field must be at least :min characters.',
    ],
    'max' => [
        'string' => 'The field must be at most :max characters.',
    ],
    'id_epp_not_found' => 'ID EPP not found',
    'minting' => [
        'error_generating_token' => 'Error generating token',
        'insufficient_wallet_balance' => 'Insufficient balance in wallet to purchase this EcoNFT',
        'error_during_save_the_metadataFile' => 'Error saving metadata to file',
        'error_during_save_the_metadata_on_database' => 'Error saving metadata to database',
        'error_during_create_metadata_file' => 'Error creating metadata file',
        'error_during_save_the_buyer' => 'Error saving buyer',
        'buyer_not_exist' => 'Buyer does not exist',
        'this_wallet_does_not_belong_to_any_buyer' => 'This wallet does not belong to any buyer',
        'seller_not_exist' => 'Seller does not exist',
        'seller_owner_not_found' => 'Seller owner not found',
        'seller_wallet_address_not_found' => 'Seller wallet address not found',
        'error_during_save_the_seller' => 'Error saving seller',
        'error_during_save_the_buyer_transaction' => 'Error saving buyer transaction',
        'error_during_the_saving_of_the_payment' => 'Error saving payment',
        'error_during_save_the_natan' => 'Error saving data', // non voglio specificare che si tratta di un errore durante il salvataggio delle royalty per Natan,
        'error_during_save_the_transaction' => 'Error saving transaction',
        'seller_not_found' => 'Seller not found',
        'error_during_the_minting' => 'Error during minting',
        'error_uploading_file' => 'Error uploading file',
        'insufficient_balance' => 'Insufficient balance',
        'eco_nft_not_found' => 'EcoNFT not found',
        'no_traits_found' => 'No traits found',
    ],
];
