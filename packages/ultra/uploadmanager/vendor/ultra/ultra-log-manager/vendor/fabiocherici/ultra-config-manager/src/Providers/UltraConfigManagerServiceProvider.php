<?php

namespace Fabio\UltraConfigManager\Providers;

use Fabio\UltraConfigManager\UltraConfigManager;
use Illuminate\Support\ServiceProvider;

class UltraConfigManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registra ConfigManager come singleton per poterlo utilizzare ovunque
        $this->app->singleton(UltraConfigManager::class, function ($app) {
            return new UltraConfigManager();
        });
    }

    public function boot()
    {
        // Qui pubblichiamo il file di configurazione per chi utilizza la libreria.
        $this->publishes([
            __DIR__ . '/../../config/ultra_config_manager.php' => config_path('ultra_x_config_manager.php'),
        ], 'config');
    }
}
