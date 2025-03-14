<?php

return [

    'default' => env('ULTRA_UPLOAD_DISK', 'public'),

    'disks' => [

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('ULTRA_AWS_ACCESS_KEY_ID'),
            'secret' => env('ULTRA_AWS_SECRET_ACCESS_KEY'),
            'region' => env('ULTRA_AWS_DEFAULT_REGION'),
            'bucket' => env('ULTRA_AWS_BUCKET'),
            'url' => env('ULTRA_AWS_URL'),
            'endpoint' => env('ULTRA_AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('ULTRA_AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'do' => [
            'driver' => 's3',
            'key' => env('ULTRA_DO_ACCESS_KEY_ID'),
            'secret' => env('ULTRA_DO_SECRET_ACCESS_KEY'),
            'region' => env('ULTRA_DO_DEFAULT_REGION'),
            'bucket' => env('ULTRA_DO_BUCKET'),
            'url' => env('ULTRA_DO_URL'),
            'endpoint' => env('ULTRA_DO_ENDPOINT'),
            'use_path_style_endpoint' => env('ULTRA_DO_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

];
