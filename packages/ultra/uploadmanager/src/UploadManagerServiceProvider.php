<?php

namespace Ultra\UploadManager;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Ultra\UploadManager\Jobs\TempFilesCleaner;
use Ultra\UploadManager\BroadcastingConfigServiceProvider;
use Ultra\UploadManager\Console\CleanTempFilesCommand;
use Ultra\UploadManager\Console\UltraSetupCommand;

class UploadManagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Log::channel('upload')->info('classe: UploadManagerServiceProvider. Method: boot()');

        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');

        // Carica le rotte per la gestione dei file temporanei di sistema
        // $this->loadRoutesFrom(__DIR__ . '/../routes/system_temp_routes.php');

        // config(['broadcasting.default' => 'pusher']);

        Log::channel('upload')->info('Boot iniziato nel UploadManagerServiceProvider');
        Log::channel('upload')->info('Configurazione broadcasting', [
            'driver' => config('broadcasting.default'),
            'connections' => array_keys(config('broadcasting.connections')),
            'pusher_config' => config('broadcasting.connections.pusher')
        ]);

        // Schedula la registrazione del canale dopo l'inizializzazione completa
        $this->app->booted(function () {
            Log::channel('upload')->info('App booted, tentativo di registrare il canale upload');
            Broadcast::channel('upload', function () {
                Log::channel('upload')->info('Qualcuno si è connesso al canale upload');
                return true;
            });
            Log::channel('upload')->info('Canale upload registrato dopo app booted');
        });

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'uploadmanager');
        $this->publishConfig();
        $this->publishLoggingConfig();

        // Registra il comando per la pulizia dei file temporanei
        if ($this->app->runningInConsole()) {
            $this->commands([
                UltraSetupCommand::class,
                CleanTempFilesCommand::class,
            ]);

            // Configura la pianificazione della pulizia dei file temporanei
            $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
                // Esegui il comando ogni giorno a mezzanotte
                $schedule->command('ultra:clean-temp')->dailyAt('00:00');

                // In alternativa, ogni 6 ore (più aggressivo)
                // $schedule->job(new TempFilesCleaner(6))->everyFourHours();
            });
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/upload-manager.php', 'upload-manager');
        $this->mergeConfigFrom(__DIR__ . '/../config/logging.php', 'logging');
        $this->mergeConfigFrom(__DIR__ . '/../config/queue.php', 'queue');
        $this->mergeConfigFrom(__DIR__ . '/../config/filesystems.php', 'filesystems');
        $this->mergeConfigFrom(__DIR__ . '/../config/AllowedFileType.php', 'AllowedFileType');

        // Registra il provider di configurazione broadcasting
        $this->app->register(BroadcastingConfigServiceProvider::class);
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
