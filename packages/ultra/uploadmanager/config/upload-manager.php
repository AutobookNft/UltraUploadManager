<?php

return [
    'upload_path' => storage_path('app/uploads'),
    'default_path' => storage_path('app/uploads'),
    'temp_path' => storage_path('app/private/temp'),
    'temp_subdir' => env('UPLOAD_MANAGER_TEMP_SUBDIR', 'ultra_upload_temp'),

    // Configurazione per l'antivirus
    'antivirus' => [
        /*
         * Il percorso del binary di ClamAV (o altro scanner).
         * Default: 'clamscan'. Assicurati che sia eseguibile sul sistema.
         * Puoi sovrascriverlo nell'env con UPLOAD_MANAGER_ANTIVIRUS_BINARY.
         */
        'binary' => env('UPLOAD_MANAGER_ANTIVIRUS_BINARY', 'clamscan'),

        /*
         * Opzioni aggiuntive per il comando ClamAV.
         * Puoi personalizzarle secondo le necessità del sistema.
         */
        'options' => [
            '--no-summary' => true, // Non mostra il sommario
            '--stdout' => true,     // Output su stdout
        ],
    ],
];
