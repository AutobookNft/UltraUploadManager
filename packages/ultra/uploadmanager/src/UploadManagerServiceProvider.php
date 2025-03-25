<?php

namespace Ultra\UploadManager;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Ultra\UploadManager\BroadcastServiceProvider;
use Ultra\UploadManager\Console\CleanTempFilesCommand;
use Ultra\UploadManager\Console\UltraSetupCommand;

class UploadManagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');

        // Log::channel('upload')->info("Configurazione broadcasting:\n" .
        //     "Driver: " . config('broadcasting.default') . "\n" .
        //     "Connections: " . implode(', ', array_keys(config('broadcasting.connections'))) . "\n" .
        //     "Pusher Config:\n" . json_encode(config('broadcasting.connections.pusher'), JSON_PRETTY_PRINT)
        // );

        // Schedula la registrazione del canale dopo l'inizializzazione completa
        $this->app->booted(function () {
            // Log::channel('upload')->info('App booted, tentativo di registrare il canale upload');
            Broadcast::channel('upload', function () {
                Log::channel('upload')->info('Qualcuno si è connesso al canale upload');
                return true;
            });
            // Log::channel('upload')->info('Canale upload registrato dopo app booted');
        });

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'uploadmanager');
        // $this->loadTranslation();

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

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/uploadmanager'),
        ], 'uploadmanager-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/uploadmanager'),
        ], 'uploadmanager-translations');

    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/upload-manager.php', 'upload-manager');
        $this->mergeConfigFrom(__DIR__ . '/../config/logging.php', 'logging');
        $this->mergeConfigFrom(__DIR__ . '/../config/queue.php', 'queue');
        $this->mergeConfigFrom(__DIR__ . '/../config/filesystems.php', 'filesystems');
        $this->mergeConfigFrom(__DIR__ . '/../config/AllowedFileType.php', 'AllowedFileType');

        // Registra il provider di configurazione broadcasting
        $this->app->register(BroadcastServiceProvider::class);
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

    protected function loadTranslation(){
        $path = __DIR__.'/../resources/lang';
        Log::channel('upload')->info('Translations path', [
            'path' => $path,
            'exists' => file_exists($path),
            'files' => file_exists($path) ? scandir($path) : 'directory non esiste'
        ]);

        // Verifichiamo anche il percorso delle lingue specifiche
        $enPath = $path . '/en';
        $itPath = $path . '/it';
        Log::channel('upload')->info('Language paths', [
            'en_path' => $enPath,
            'en_exists' => file_exists($enPath),
            'en_files' => file_exists($enPath) ? scandir($enPath) : 'directory non esiste',
            'it_path' => $itPath,
            'it_exists' => file_exists($itPath),
            'it_files' => file_exists($itPath) ? scandir($itPath) : 'directory non esiste',
        ]);
    }
}
