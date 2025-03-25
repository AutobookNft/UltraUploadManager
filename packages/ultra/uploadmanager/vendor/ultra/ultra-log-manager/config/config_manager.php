<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Config file for the library
    |--------------------------------------------------------------------------
    |
    | The name of the configuration file used by the library. This file should
    | be placed in the config directory of the Laravel application. The value
    | can be set in the .env file using the variable ULTRA_LOG_MANAGER_CONFIG_FILE.
    | By default, it will use "ultra_log_manager.php".
    */
    'config_file' => 'ultra_log_manager.php',
        
    
    /*
    |--------------------------------------------------------------------------
    | Log Channel Configuration
    |--------------------------------------------------------------------------
    |
    | Defines the log channel used by the error manager. This allows you to 
    | specify which logging channel should be used to record errors. The value
    | can be set in the .env file using the variable ULTRA_LOG_MANAGER_LOG_CHANNEL.
    | By default, it will use the "log_manager" channel.
    |
    | Example: ULTRA_LOG_MANAGER_LOG_CHANNEL= log_manager
    |
    */
    'log_channel' => env('ULTRA_LOG_MANAGER_LOG_CHANNEL', 'log_manager'),


    /*
    |--------------------------------------------------------------------------
    | Initial depth of debug_backtrace
    |--------------------------------------------------------------------------
    |
    | The initial depth of the debug_backtrace function used to retrieve the
    | caller context. This value can be set in the .env file using the variable
    | ULTRA_LOG_MANAGER_BACKTRACE_DEPTH. By default, it will use 3.
    |
    | Example: ULTRA_LOG_MANAGER_BACKTRACE_DEPTH=3
    */
    'log_backtrace_depth' => env('ULTRA_LOG_MANAGER_BACKTRACE_DEPTH', 3),

    /*
    |--------------------------------------------------------------------------
    | Maximum depth limit in debug_backtrace
    |--------------------------------------------------------------------------
    |
    | The maximum depth limit of the debug_backtrace function used to retrieve
    | the caller context. This value can be set in the .env file using the variable
    | ULTRA_LOG_MANAGER_BACKTRACE_LIMIT. By default, it will use 5.
    |
    | Example: ULTRA_LOG_MANAGER_BACKTRACE_LIMIT=5
    */
    'backtrace_limit' => env('ULTRA_LOG_MANAGER_BACKTRACE_LIMIT', 7),


    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | A list of supported languages for error messages and other localization
    | needs. This value can be set in the .env file using the variable
    | ULTRA_LOG_MANAGER_SUPPORTED_LANGUAGES, and it should be a comma-separated string
    | of language codes (e.g., "it,en,fr,es,pt,de"). By default, it supports
    | Italian, English, French, Spanish, Portuguese, and German.
    |
    | Example: ULTRA_LOG_MANAGER_SUPPORTED_LANGUAGES=it,en,fr,es,pt,de
    |
    */
    'supported_languages' => explode(',', env('ULTRA_LOG_MANAGER_SUPPORTED_LANGUAGES', 'it,en,fr,es,pt,de')),

    /*
    |--------------------------------------------------------------------------
    | DevTeam Email Address
    |--------------------------------------------------------------------------
    |
    | The email address used for notifying the development team in case of 
    | critical errors. The value can be set in the .env file using the variable
    | ULTRA_LOG_MANAGER_DEVTEAM_EMAIL. By default, it will use "devteam@gmail.com".
    |
    | Example: ULTRA_LOG_MANAGER_DEVTEAM_EMAIL=devteam@example.com
    |
    */
    'devteam_email' => env('ULTRA_LOG_MANAGER_DEVTEAM_EMAIL', 'devteam@gmail.com'),

    /*
    |--------------------------------------------------------------------------
    | Send Email to DevTeam in Case of Error
    |--------------------------------------------------------------------------
    |
    | Determines whether the system should send an email to the development 
    | team when a critical error occurs. This setting helps ensure the DevTeam 
    | is aware of serious issues immediately. This can be overridden in the 
    | .env file if needed.
    |
    | Example: ULTRA_LOG_MANAGER_EMAIL_NOTIFICATION=true
    |
    */
    'email_notifications' => env('ULTRA_LOG_MANAGER_EMAIL_NOTIFICATION', false),

];
