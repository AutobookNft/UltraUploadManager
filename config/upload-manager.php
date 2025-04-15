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

    /**
     * Maximum total size for all files uploaded in a single request (in bytes or human-readable string).
     * Taken from your config: 'collection' => ['max_size' => 104857600] (100 MB).
     */
    'max_total_size' => '100M', // 104857600 bytes

    /**
     * Maximum size for a single file (in bytes or human-readable string).
     * Your config defines category-specific limits in 'size_limits' (e.g., 20 MB for images).
     * Since getUploadLimits() uses a single value, we take the most restrictive (20 MB) as a general default.
     */
    'max_file_size' => '20M', // 20971520 bytes

    /**
     * Maximum number of files that can be uploaded in a single request.
     * Not explicitly defined in your original config, so we set a reasonable default (50).
     */
    'max_files' => 50,

    /**
     * Safety margin for size limits, used in getUploadLimits() to account for overhead.
     * Taken from your code: config('upload-manager.size_margin', 1.1).
     */
    'size_margin' => 1.1,

    /**
     * Optional: List of allowed file extensions for validation.
     * Copied from your config: 'collection' => ['allowed_extensions'].
     */
    'allowed_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp', 'svg', 'eps', 'psd', 'ai', 'cdr', // Images
        'pdf', 'epub', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf', // Documents
        'mp3', 'wav', 'm4a', 'ape', 'flac', // Audio
        'mp4', 'mov', 'avi', 'mkv' // Video
    ],

    /**
     * Optional: List of allowed MIME types for file validation.
     * Copied from your config: 'collection' => ['allowed_mime_types'].
     */
    'allowed_mime_types' => [
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp',
        'image/svg+xml', 'image/tiff', 'application/postscript',
        'image/vnd.adobe.photoshop', 'application/illustrator',
        'application/x-coreldraw',
        // Documents
        'application/pdf', 'application/epub+zip', 'text/plain',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation', 'application/rtf',
        'text/html',
        // Audio
        'audio/mpeg', 'audio/wav', 'audio/x-m4a', 'audio/ape', 'audio/flac',
        // Video
        'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'
    ],
];
