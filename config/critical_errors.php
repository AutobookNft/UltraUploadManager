<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Critical Error Codes
    |--------------------------------------------------------------------------
    |
    | Questo array contiene i codici degli errori che devono essere considerati
    | critici. Quando uno di questi errori si verifica, il sistema invierà una
    | notifica via email al DevTeam.
    |
    */

    'codes' => [
        '500',
        '501',
        '502',
        '503',
        '504',
        'ERROR_DURING_FILE_UPLOAD',
        'ERROR_DELETING_LOCAL_TEMP_FILE',
        'ERROR_DELETING_EXT_TEMP_FILE',
        'SCAN_ERROR',
        'ERROR_DELETING_TEMP_FILE',
        'ERROR_GETTING_PRESIGNED_URL',
        'IMPOSSIBLE_SAVE_FILE',
        'UNABLE_TO_SAVE_BOT_FILE',
        'UNABLE_TO_CREATE_DIRECTORY',
        'UNABLE_TO_CHANGE_PERMISSIONS',
        'ERROR_DURING_CREATE_EGI_RECORD',
        'ERROR_DURING_FILE_NAME_ENCRYPTION'
        
    ],
];
