<?php

return [
    'build_directory' => 'public/build',
    'hot_file' => 'public/build/hot',
    'asset_url' => null,
    'public_directory' => 'public',
    'dev_server' => [
        'host' => 'localhost',
        'port' => 5173, // Deve corrispondere al port in vite.config.js
        'use_tls' => false,
    ],
];
