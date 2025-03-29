<?php

return [
    App\Providers\AppServiceProvider::class,
    Ultra\TranslationManager\Providers\UltraTranslationServiceProvider::class,
    Ultra\UploadManager\UploadManagerServiceProvider::class,
    Ultra\UploadManager\BroadcastServiceProvider::class,
    Ultra\ErrorManager\Providers\UltraErrorManagerServiceProvider::class,
    // Ultra\UltraLogManager\UltraLogManagerServiceProvider::class,
    // Ultra\UltraConfigManager\UltraConfigManagerServiceProvider::class,
];
