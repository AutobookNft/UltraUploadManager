<?php

namespace Ultra\UploadManager;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Ultra\UploadManager\BroadcastServiceProvider;
use Ultra\UploadManager\Console\CleanTempFilesCommand;
use Ultra\UploadManager\Console\UltraSetupCommand;
use Ultra\UploadManager\Services\SizeParser;
use Illuminate\Contracts\Translation\Translator as TranslatorContract; // Importa l'interfaccia

class UploadManagerServiceProvider extends ServiceProvider
{
    public function boot()
    {

        Log::channel('upload')->info('UploadManagerServiceProvider booted');


        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'uploadmanager');

        // Chiama il metodo helper dedicato
        $this->registerPackageTranslationsWithUtm();

        // $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'uploadmanager');
        // Log::channel('upload')->info('[UUM] Caricamento traduzioni standard Laravel per fallback.');

        // ... altre registrazioni in boot (canali, comandi, publish) ...
        $this->publishConfig();
        $this->publishLoggingConfig(); // Forse questa era già qui?
        $this->registerCommandsAndSchedule(); // Esempio di ulteriore estrazione
        $this->registerPublishing(); // Esempio di ulteriore estrazione
        $this->registerEchoChannel(); // Esempio di ulteriore estrazione

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

    /**
     * Registers the package's translations with UltraTranslationManager.
     * Logs errors if UTM is not available or registration fails.
     * (Following Option 1: UTM is mandatory)
     */
    protected function registerPackageTranslationsWithUtm(): void
    {

        // 1. Ottieni la directory del file corrente (src)
        $srcDir = __DIR__; // Es: /home/fabio/.../uploadmanager/src

        // 2. Sali di un livello per ottenere la directory base del pacchetto
        $packageBaseDir = dirname($srcDir); // Es: /home/fabio/.../uploadmanager

        // 3. Aggiungi il percorso relativo alla directory lang
        $langPath = $packageBaseDir . '/resources/lang'; // Es: /home/fabio/.../uploadmanager/resources/lang

        if ($this->app->bound('ultra.translation')) { // Verifica se UTM è disponibile
            try {
                app('ultra.translation')->registerPackageTranslations(
                    'uploadmanager',
                    $langPath
                );
                Log::channel('upload')->info('[UUM] Traduzioni registrate tramite UltraTranslationManager: ' . $langPath);

            } catch (\Exception $e) {
                Log::channel('upload')->error('[UUM] Errore fatale: impossibile registrare traduzioni con UTM. ' . $e->getMessage());
                // Potresti lanciare un'eccezione qui
                // throw new \RuntimeException("UTM richiesto ma registrazione fallita.", 0, $e);
            }
        } else {
             Log::channel('upload')->error('[UUM] Errore fatale: UltraTranslationManager (ultra.translation) non trovato.');
             // Lancia un'eccezione o logga errore grave
             // throw new \RuntimeException("UltraTranslationManager non trovato.");
        }
    }

    public function register()
    {

        Log::channel('upload')->info('UploadManagerServiceProvider registered');

        $this->mergeConfigFrom(__DIR__ . '/../config/upload-manager.php', 'upload-manager');
        $this->mergeConfigFrom(__DIR__ . '/../config/logging.php', 'logging');
        $this->mergeConfigFrom(__DIR__ . '/../config/queue.php', 'queue');
        $this->mergeConfigFrom(__DIR__ . '/../config/filesystems.php', 'filesystems');
        $this->mergeConfigFrom(__DIR__ . '/../config/AllowedFileType.php', 'AllowedFileType');

        // Registra il provider di configurazione broadcasting
        $this->app->register(BroadcastServiceProvider::class);

        $this->app->singleton(SizeParser::class, function ($app) {
            return new SizeParser();
        });
    }

     /**
     * Registers console commands and scheduled tasks.
     */
    protected function registerCommandsAndSchedule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UltraSetupCommand::class,
                CleanTempFilesCommand::class,
            ]);

            $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('ultra:clean-temp')->dailyAt('00:00');
            });
        }
    }

    /**
     * Registers resource publishing.
     */
    protected function registerPublishing(): void
    {
         $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/uploadmanager'),
        ], 'uploadmanager-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/uploadmanager'), // Usa lang_path()
        ], 'uploadmanager-translations');

        $this->publishes([
            __DIR__ . '/../config/upload-manager.php' => config_path('upload-manager.php'),
        ], 'upload-manager-config');

         $this->publishes([
            __DIR__ . '/../config/logging.php' => config_path('logging-ultra-uploadmanager.php'),
        ], 'upload-manager-logging');
    }

     /**
     * Registers the Echo broadcast channel.
     */
    protected function registerEchoChannel(): void
    {
         $this->app->booted(function () {
            Broadcast::channel('upload', function () {
                Log::channel('upload')->info('Qualcuno si è connesso al canale upload');
                return true;
            });
        });
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

    // protected function loadTranslation(){
    //     $path = __DIR__.'/../resources/lang';
    //     Log::channel('upload')->info('Translations path', [
    //         'path' => $path,
    //         'exists' => file_exists($path),
    //         'files' => file_exists($path) ? scandir($path) : 'directory non esiste'
    //     ]);

    //     // Verifichiamo anche il percorso delle lingue specifiche
    //     $enPath = $path . '/en';
    //     $itPath = $path . '/it';
    //     Log::channel('upload')->info('Language paths', [
    //         'en_path' => $enPath,
    //         'en_exists' => file_exists($enPath),
    //         'en_files' => file_exists($enPath) ? scandir($enPath) : 'directory non esiste',
    //         'it_path' => $itPath,
    //         'it_exists' => file_exists($itPath),
    //         'it_files' => file_exists($itPath) ? scandir($itPath) : 'directory non esiste',
    //     ]);
    // }
}
