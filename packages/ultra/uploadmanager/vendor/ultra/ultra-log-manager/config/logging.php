<?php

use Fabio\UltraLogManager\Logging\CustomizeFormatter;

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

    'default' => env('ULTRA_LOG_MANAGER_LOG_CHANNEL', 'ultra_log_manager'),

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
        
        'error_manager' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ultra_log_manager.log'),
            'level' => 'debug',
            'tap' => [CustomizeFormatter::class], // Mantienilo solo se necessario per formattare i log
            'days' => 7,  // Numero di giorni per cui conservare i log
        ],
        
    ],

];
