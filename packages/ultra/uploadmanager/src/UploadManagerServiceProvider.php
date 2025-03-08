<?php

namespace Ultra\UploadManager;
use Illuminate\Support\ServiceProvider;

class UploadManagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'uploadmanager');
        $this->publishConfig();
        $this->publishLoggingConfig();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/upload-manager.php', 'upload-manager');
        $this->mergeConfigFrom(__DIR__ . '/../config/logging.php', 'logging');
    }

    protected function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/upload-manager.php' => config_path('upload-manager.php'),
        ], 'upload-manager-config');
    }

    protected function publishLoggingConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/logging.php' => config_path('logging-ultra-uploadmanager.php'),
        ], 'upload-manager-logging');
    }
}
