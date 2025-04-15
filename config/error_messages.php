<?php

// config/error_messages.php

return [
    'TEMP_FILE_NOT_FOUND' => [
        'type' => 'warning',
        'blocking' => 'semi-blocking',
        'dev_message' => 'Temporary file not found.',
        'http_status_code' => 404,
        'devTeam_email_need' => false,
    ],
    'AUTHENTICATION_ERROR' => [
        'type' => 'blocking',
        'blocking' => 'blocking',
        'dev_message' => 'Tentativo di accesso non autenticato.',
        'http_status_code' => 401,
        'devTeam_email_need' => false,
    ],
    'UNEXPECTED_ERROR' => [
        'type' => 'critical',
        'blocking' => 'semi-blocking',
        'dev_message' => 'Errore imprevisto nel sistema.',
        'http_status_code' => 400,
        'devTeam_email_need' => true,
    ],
    'INVALID_IMAGE_STRUCTURE' => [
        'type' => 'warning',
        'blocking' => 'semi-blocking',
        'dev_message' => 'The structure of the image file is invalid.',
        'http_status_code' => 400,
        'devTeam_email_need' => false,
    ],
    'MIME_TYPE_NOT_ALLOWED' => [
        'type' => 'warning',
        'blocking' => 'semi-blocking',
        'dev_message' => 'The MIME type of the file is not allowed.',
        'http_status_code' => 400,
        'devTeam_email_need' => false,
    ],
    'MAX_FILE_SIZE' => [
        'type' => 'warning',
        'blocking' => 'semi-blocking',
        'dev_message' => 'The file exceeds the maximum allowed size.',
        'http_status_code' => 413,
        'devTeam_email_need' => false,
    ],
    'INVALID_FILE_EXTENSION' => [
        'type' => 'warning',
        'blocking' => 'semi-blocking',
        'dev_message' => 'The file has an invalid extension.',
        'http_status_code' => 400,
        'devTeam_email_need' => false,
    ],
    'INVALID_FILE_NAME' => [
        'type' => 'blocking',
        'blocking' => 'semi-blocking',
        'dev_message' => 'Invalid file name received during upload process.',
        'http_status_code' => 400,
        'devTeam_email_need' => false,
    ],
    'ERROR_GETTING_PRESIGNED_URL' => [
        'type' => 'critical',
        'blocking' => 'not',
        'dev_message' => 'An error occurred while retrieving the presigned URL.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'ERROR_DURING_FILE_UPLOAD' => [
        'type' => 'critical',
        'blocking' => 'semi-blocking',
        'dev_message' => 'An error occurred during the file upload process.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'ERROR_DELETING_LOCAL_TEMP_FILE' => [
        'type' => 'critical',
        'blocking' => 'not',
        'dev_message' => 'Failed to delete the local temporary file.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'ERROR_DELETING_EXT_TEMP_FILE' => [
        'type' => 'critical',
        'blocking' => 'not',
        'dev_message' => 'Failed to delete the external temporary file.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'VIRUS_FOUND' => [
        'type' => 'warning', // Corrected from 'warining' to 'warning'
        'blocking' => 'semi-blocking', // Changed 'block' to 'blocking' for consistency
        'dev_message' => 'A virus was detected in the file.',
        'http_status_code' => 422,
        'devTeam_email_need' => false,
    ],
    'SCAN_ERROR' => [
        'type' => 'warning',
        'blocking' => 'semi-blocking',
        'dev_message' => 'An error occurred during the virus scan.',
        'http_status_code' => 400,
        'devTeam_email_need' => false,
    ],
    'UNABLE_TO_SAVE_BOT_FILE' => [
        'type' => 'critical',
        'blocking' => 'semi-blocking',
        'dev_message' => 'Unable to save the file for the bot.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'INVALID_FILE_PDF' => [
        'type' => 'warning',
        'blocking' => 'semi-blocking',
        'dev_message' => 'The PDF file is invalid.',
        'http_status_code' => 400,
        'devTeam_email_need' => false,
    ],
    'UNABLE_TO_CREATE_DIRECTORY' => [
        'type' => 'critical',
        'blocking' => 'not',
        'dev_message' => 'Failed to create directory for file upload.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'UNABLE_TO_CHANGE_PERMISSIONS' => [
        'type' => 'critical',
        'blocking' => 'not',
        'dev_message' => 'Failed to change file permissions.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'IMPOSSIBLE_SAVE_FILE' => [
        'type' => 'critical',
        'blocking' => 'semi-blocking',
        'dev_message' => 'It was impossible to save the file.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'ERROR_DURING_CREATE_EGI_RECORD' => [
        'type' => 'critical',
        'blocking' => 'blocking',
        'dev_message' => 'An error occurred while creating the EGI record in the database.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'ACL_SETTING_ERROR' => [
        'type' => 'critical',
        'blocking' => 'semi-blocking',
        'dev_message' => 'An error occurred while setting the ACL.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'ERROR_DURING_FILE_NAME_ENCRYPTION' => [
        'type' => 'critical',
        'blocking' => 'semi-blocking',
        'dev_message' => 'An error occurred during the file name encryption process.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'IMAGICK_NOT_AVAILABLE' => [
        'type' => 'critical',
        'blocking' => 'blocking',
        'dev_message' => 'The Imagick extension is not available.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'GENERIC_SERVER_ERROR' => [
        'type' => 'critical', // Changed from 'simulazione_errore_server' to 'critical' for consistency
        'blocking' => 'blocking',
        'dev_message' => 'A generic server error occurred.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
    'FILE_NOT_FOUND' => [
        'type' => 'warning',
        'blocking' => 'semi-blocking',
        'dev_message' => 'The requested file was not found.', // Added a descriptive message
        'http_status_code' => 404, // Changed from 500 to 404 for "Not Found"
        'devTeam_email_need' => false,
    ],
    'JSON_ERROR' => [
        'type' => 'critical', // Changed from 'json_error' to 'critical' for consistency
        'blocking' => 'semi-blocking',
        'dev_message' => 'JSON error in the dispatcher.',
        'http_status_code' => 500,
        'devTeam_email_need' => true,
    ],
];




