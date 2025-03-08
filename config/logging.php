    <?php

    use Ultra\UploadManager\Logging\CustomizeFormatter;
    use Monolog\Handler\NullHandler;
    use Monolog\Handler\StreamHandler;
    use Monolog\Handler\SyslogUdpHandler;


    return [

        /*
        |--------------------------------------------------------------------------
        | Default Log Channel
        |--------------------------------------------------------------------------
        |
        | This option defines the default log channel that gets used when writing
        | messages to the logs. The name specified in this option should match
        | one of the channels defined in the "channels" configuration array.
        |
        */

        'default' => env('LOG_CHANNEL', 'stack'),

        /*
        |--------------------------------------------------------------------------
        | Deprecations Log Channel
        |--------------------------------------------------------------------------
        |
        | This option controls the log channel that should be used to log warnings
        | regarding deprecated PHP and library features. This allows you to get
        | your application ready for upcoming major versions of dependencies.
        |
        */

        'deprecations' => [
            'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
            'trace' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Log Channels
        |--------------------------------------------------------------------------
        |
        | Here you may configure the log channels for your application. Out of
        | the box, Laravel uses the Monolog PHP logging library. This gives
        | you a variety of powerful log handlers / formatters to utilize.
        |
        | Available Drivers: "single", "daily", "slack", "syslog",
        |                    "errorlog", "monolog",
        |                    "custom", "stack"
        |
        */

        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single'],
                'ignore_exceptions' => false,
            ],

            'single' => [
                'driver' => 'daily',
                'path' => storage_path('logs/laravel.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 14,
            ],

            'daily' => [
                'driver' => 'daily',
                'path' => storage_path('logs/laravel.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 14,
            ],

            'slack' => [
                'driver' => 'slack',
                'url' => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'Laravel Log',
                'emoji' => ':boom:',
                'level' => env('LOG_LEVEL', 'critical'),
            ],

            'papertrail' => [
                'driver' => 'monolog',
                'level' => env('LOG_LEVEL', 'debug'),
                'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
                'handler_with' => [
                    'host' => env('PAPERTRAIL_URL'),
                    'port' => env('PAPERTRAIL_PORT'),
                    'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
                ],
            ],

            'stderr' => [
                'driver' => 'monolog',
                'level' => env('LOG_LEVEL', 'debug'),
                'handler' => StreamHandler::class,
                'formatter' => env('LOG_STDERR_FORMATTER'),
                'with' => [
                    'stream' => 'php://stderr',
                ],
            ],

            'syslog' => [
                'driver' => 'syslog',
                'level' => env('LOG_LEVEL', 'debug'),
            ],

            'errorlog' => [
                'driver' => 'errorlog',
                'level' => env('LOG_LEVEL', 'debug'),
            ],

            'null' => [
                'driver' => 'monolog',
                'handler' => NullHandler::class,
            ],

            'emergency' => [
                'path' => storage_path('logs/laravel.log'),
            ],

            'nft_transaction' => [
                'driver' => 'daily',
                'path' => storage_path('logs/NFT_transaction.log'),
                'level' => 'debug',
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],

            'minting' => [
                'driver' => 'daily',
                'path' => storage_path('logs/minting.log'),
                'level' => 'debug',
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],

            'upload' => [
                'driver' => 'daily',
                'path' => storage_path('logs/UltraUploadManager.log'),
                'level' => 'debug',
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],

            'loading' => [
                'driver' => 'daily',
                'path' => storage_path('logs/loading.log'),
                'level' => 'debug',
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],

            'traits' => [
                'driver' => 'daily',
                'path' => storage_path('logs/traits.log'),
                'level' => 'debug',
                // 'tap' => [CustomizeFormatter::class],
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],

            'nftflorence' => [
                'driver' => 'daily',
                'path' => storage_path('logs/nftflorence.log'),
                'level' => 'debug',
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],

            'tests' => [
                'driver' => 'daily',
                'path' => storage_path('logs/tests.log'),
                'level' => 'debug',
                'tap' => [CustomizeFormatter::class],
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],

            'javascript' => [
                'driver' => 'daily',
                'path' => storage_path('logs/javascript.log'),
                'level' => 'debug',
                'tap' => [CustomizeFormatter::class],
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],


            'bind_unbind_cover' => [
                'driver' => 'daily',
                'path' => storage_path('logs/bind_unbind_cover.log'),
                'level' => 'debug',
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],

            'collections' => [
                'driver' => 'daily',
                'path' => storage_path('logs/collections.log'),
                'level' => 'debug',
                // 'tap' => [CustomizeFormatter::class],
                'days' => 7,  // Numero di giorni per cui conservare i log
            ],
            'services' => [
                'driver' => 'daily',
                'path' => storage_path('logs/services.log'),
                'level' => 'debug',
                'days' => 7,
            ],

            'stac' => [
                'driver' => 'daily',
                'path' => storage_path('logs/stac.log'),
                'level' => 'debug',
                'days' => 7,
            ],
        ],

    ];
