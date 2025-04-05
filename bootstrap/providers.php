<?php

return [
    App\Providers\AppServiceProvider::class,
    Ultra\TranslationManager\Providers\UltraTranslationServiceProvider::class,
    Ultra\UploadManager\UploadManagerServiceProvider::class,
    Ultra\UploadManager\BroadcastServiceProvider::class,
    Ultra\ErrorManager\Providers\UltraErrorManagerServiceProvider::class,
    Ultra\UltraConfigManager\Providers\UConfigServiceProvider::class,
    Ultra\TranslationManager\Providers\UltraTranslationServiceProvider::class,
];
