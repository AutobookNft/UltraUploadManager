<?php

namespace Ultra\UltraLogManager;

return [
    'log_channel' => env('ULTRA_LOG_MANAGER_LOG_CHANNEL', 'error_manager'),
    'log_level' => env('ULTRA_LOG_MANAGER_LOG_LEVEL', 'debug'), // Nuovo
    'log_backtrace_depth' => env('ULTRA_LOG_MANAGER_BACKTRACE_DEPTH', 3),
    'backtrace_limit' => env('ULTRA_LOG_MANAGER_BACKTRACE_LIMIT', 7),
    'supported_languages' => explode(',', env('ULTRA_LOG_MANAGER_SUPPORTED_LANGUAGES', 'it,en,fr,es,pt,de')),
    'devteam_email' => env('ULTRA_LOG_MANAGER_DEVTEAM_EMAIL', 'devteam@gmail.com'),
    'email_notifications' => env('ULTRA_LOG_MANAGER_EMAIL_NOTIFICATION', false),
];