    <?php

    use Ultra\UploadManager\Logging\CustomizeFormatter;

    return [
        'upload' => [
            'driver' => 'daily',
            'path' => storage_path('logs/UltraUploadManager.log'),
            'level' => 'debug',
            'days' => 7,  // Numero di giorni per cui conservare i log
        ],

        'javascript' => [
            'driver' => 'daily',
            'path' => storage_path('logs/javascript.log'),
            'level' => 'debug',
            'tap' => [CustomizeFormatter::class],
            'days' => 7,  // Numero di giorni per cui conservare i log
        ],
    ];
