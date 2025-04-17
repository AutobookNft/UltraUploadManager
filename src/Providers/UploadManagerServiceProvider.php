<?php

/**
 * Service Provider for the UltraUploadManager (UUM) package.
 *
 * Registers UUM services, configuration, routes, commands, broadcasting,
 * and handles resource loading and publishing within the Laravel application.
 * Acts as the primary integration point for the UUM package.
 *
 * @package     Ultra\UploadManager\Providers // Updated namespace if moved
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.1.0 // Refactored for Oracode v1.5.0 and Ultra Ecosystem integration.
 * @since       1.0.0
 *
 * @see \Ultra\UploadManager\Console\CleanTempFilesCommand Registered console command.
 * @see \Ultra\UploadManager\Console\UltraSetupCommand Registered console command.
 * @see \Ultra\UploadManager\Services\SizeParser Service registered.
 * @see \Ultra\UploadManager\Providers\BroadcastServiceProvider Registered broadcast provider.
 */

namespace Ultra\UploadManager\Providers; // Assuming it's in Providers subdir

// Laravel Contracts & Facades
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator as TranslatorContract; // Still needed for type hint potentially
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast; // For Echo channel
use Illuminate\Console\Scheduling\Schedule;

// UUM Components
use Ultra\UploadManager\Console\CleanTempFilesCommand;
use Ultra\UploadManager\Console\UltraSetupCommand;
use Ultra\UploadManager\Services\SizeParser;
use Ultra\UploadManager\Providers\BroadcastServiceProvider; // UUM's specific broadcast provider

// Ultra Ecosystem Dependencies
use Psr\Log\LoggerInterface; // Use PSR-3 Logger Interface (ULM provides implementation)
use Throwable;
use Ultra\TranslationManager\Contracts\TranslationManagerContract; // Use UTM Contract
use Ultra\ErrorManager\Interfaces\ErrorManagerInterface; // Use UEM Contract

class UploadManagerServiceProvider extends ServiceProvider
{
    /**
     * The base path for package resources, calculated once.
     * @var string
     */
    protected string $packageBasePath;

    /**
     * PSR-3 Logger instance (resolved ULM).
     * @var LoggerInterface|null
     */
    protected ?LoggerInterface $logger = null;

    /**
     * Ultra Error Manager instance (resolved UEM).
     * @var ErrorManagerInterface|null
     */
    protected ?ErrorManagerInterface $errorManager = null;

    /**
     * Get the PSR-3 logger instance, resolving it if needed.
     * Logs an error via UEM if the logger cannot be resolved.
     *
     * @return LoggerInterface
     */
    protected function logger(): LoggerInterface
    {
        if ($this->logger === null) {
            // Attempt to resolve LoggerInterface (ULM should be bound to this)
            if ($this->app->bound(LoggerInterface::class)) {
                $this->logger = $this->app->make(LoggerInterface::class);
            } else {
                // Fallback or critical error if ULM isn't bound correctly
                // For now, let's try a basic log channel as fallback if possible
                try {
                     $this->logger = $this->app->make('log')->channel(config('ultra_log_manager.log_channel', 'stack'));
                     $this->errorManager()?->handle('UUM_ULM_BINDING_MISSING', [ // Log via UEM if available
                         'provider' => static::class,
                         'method' => 'logger',
                         'message' => 'LoggerInterface (ULM) not bound, using fallback logger.',
                     ]);
                } catch (\Throwable $e) {
                     // Ultimate fallback if even basic logger fails
                     error_log('CRITICAL UUM ERROR: Could not resolve LoggerInterface (ULM) and fallback logger failed.');
                     // Re-throw or handle based on severity - Provider errors are tricky
                     throw new \RuntimeException('UUM requires a PSR-3 Logger binding (like ULM).', 0, $e);
                }
            }
        }
        return $this->logger;
    }

    /**
     * Get the UEM instance, resolving it if needed.
     * Returns null if resolution fails (prevents infinite loop if UEM itself fails).
     *
     * @return ErrorManagerInterface|null
     */
    protected function errorManager(): ?ErrorManagerInterface
    {
        if ($this->errorManager === null) {
             if ($this->app->bound(ErrorManagerInterface::class)) {
                $this->errorManager = $this->app->make(ErrorManagerInterface::class);
             } else {
                 error_log('CRITICAL UUM ERROR: Could not resolve ErrorManagerInterface (UEM). Error handling might be compromised.');
                 // Avoid calling UEM here to prevent loops
             }
        }
        return $this->errorManager;
    }


    /**
     * Register package services.
     *
     * Merges configuration files and registers the BroadcastServiceProvider and SizeParser.
     * Does not bind the main UUM functionalities here, as they might depend on booted services.
     *
     * @return void
     * @sideEffect Merges 'upload-manager', 'logging', 'queue', 'filesystems', 'AllowedFileType' configurations.
     * @sideEffect Registers BroadcastServiceProvider and SizeParser singleton.
     * @see \Ultra\UploadManager\Providers\BroadcastServiceProvider
     * @see \Ultra\UploadManager\Services\SizeParser
     */
    public function register(): void
    {
        $this->packageBasePath = dirname(__DIR__, 2); // Calculate base path once

        $this->logger()?->info('[UUM] Registering UploadManagerServiceProvider.'); // Use injected logger

        // Merge configurations needed by UUM
        $this->mergeConfigFrom($this->packageBasePath . '/config/upload-manager.php', 'upload-manager');
        // Note: Merging logging, queue, filesystems, AllowedFileType might overwrite app settings.
        // Consider if these should be published only, or if merging is truly needed.
        // For safety, commenting out potentially conflicting merges. Publish is better.
        $this->mergeConfigFrom($this->packageBasePath . '/config/logging.php', 'logging');
        $this->mergeConfigFrom($this->packageBasePath . '/config/queue.php', 'queue');
        $this->mergeConfigFrom($this->packageBasePath . '/config/filesystems.php', 'filesystems');
        $this->mergeConfigFrom($this->packageBasePath . '/config/AllowedFileType.php', 'AllowedFileType');
        $this->logger()?->debug('[UUM] Default configuration merged.', ['key' => 'upload-manager']);

        // Register UUM's specific BroadcastServiceProvider
        $this->app->register(BroadcastServiceProvider::class);
        $this->logger()?->debug('[UUM] BroadcastServiceProvider registered.');

        // Register SizeParser as a singleton service
        $this->app->singleton(SizeParser::class, function ($app) {
            return new SizeParser();
        });
        $this->logger()?->debug('[UUM] SizeParser service bound as singleton.');
    }

    /**
     * Bootstrap package services and resources after registration.
     *
     * Loads routes, views, translations (via UTM). Defines publishable assets.
     * Registers console commands and schedules the temp file cleaner job.
     * Registers the Echo broadcast channel.
     *
     * @return void
     * @sideEffect Loads routes, views, translations. Defines publishing groups. Registers commands and schedules job. Registers Echo channel.
     * @see self::loadPackageRoutes()
     * @see self::loadPackageViews()
     * @see self::registerPackageTranslations()
     * @see self::definePublishing()
     * @see self::registerCommandsAndScheduling()
     * @see self::registerEchoChannel()
     */
    public function boot(): void
    {
        $this->logger()?->info('[UUM] Booting UploadManagerServiceProvider.');

        // Load core package resources
        $this->loadPackageRoutes();
        $this->loadPackageViews();
        $this->registerPackageTranslations(); // Uses UTM

        // Define what can be published
        $this->definePublishing();

        // Register console commands and scheduler task
        $this->registerCommandsAndScheduling();

        // Register the broadcast channel
        $this->registerEchoChannel();

        $this->logger()?->info('[UUM] UploadManagerServiceProvider booted successfully.');
    }

    /**
     * Load the package's routes.
     * Routes are loaded from the package's routes directory.
     *
     * @return void
     * @sideEffect Registers package routes within the application.
     */
    protected function loadPackageRoutes(): void
    {
        $routesPath = $this->packageBasePath . '/routes/routes.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
            $this->logger()?->debug('[UUM] Package routes loaded.', ['path' => $routesPath]);
        } else {
            $this->logger()?->warning('[UUM] Package routes file not found, skipping.', ['path' => $routesPath]);
        }
    }

    /**
     * Load the package's views.
     * Views are namespaced under 'uploadmanager'.
     *
     * @return void
     * @sideEffect Registers package views with the application's view finder.
     */
    protected function loadPackageViews(): void
    {
        $viewsPath = $this->packageBasePath . '/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'uploadmanager');
            $this->logger()?->debug('[UUM] Package views loaded.', ['namespace' => 'uploadmanager', 'path' => $viewsPath]);
        } else {
             $this->logger()?->warning('[UUM] Package views directory not found, skipping.', ['path' => $viewsPath]);
        }
    }

    /**
     * Register the package's translations with the application.
     *
     * This method uses the standard Laravel `loadTranslationsFrom` helper.
     * It relies on the application's service container correctly providing an
     * implementation of `Illuminate\Contracts\Translation\Translator` (which
     * should be UltraTranslationManager if it's registered correctly).
     * It logs the process and handles potential exceptions during loading using UEM/ULM.
     *
     * @return void
     * @sideEffect Registers the 'uploadmanager' translation namespace.
     * @see \Illuminate\Support\ServiceProvider::loadTranslationsFrom() For the underlying Laravel helper.
     * @see \Illuminate\Contracts\Translation\Translator Contract being used implicitly.
     */
    protected function registerPackageTranslations(): void
    {
        $langPath = $this->packageBasePath . '/resources/lang';
        $namespace = 'uploadmanager';

        // Check if the language directory exists before attempting to load
        if (!is_dir($langPath)) {
            $this->logger()?->warning( // Use warning level for missing directory
                '[UUM Provider] Package language directory not found, skipping translation registration.',
                ['path' => $langPath]
            );
            // Optionally handle this via UEM if considered an error state
            // $this->errorManager()?->handle('UUM_LANG_DIR_MISSING', ['path' => $langPath]);
            return; // Exit the method if directory is missing
        }

        $this->logger()?->debug( // Use debug level for standard operation info
            '[UUM Provider] Attempting to register translation namespace using standard method.',
            ['namespace' => $namespace, 'path' => $langPath]
        );

        try {
            // Use the standard Laravel helper to load translations.
            // This will utilize whatever Translator implementation is bound in the container (ideally UTM).
            $this->loadTranslationsFrom($langPath, $namespace);

            $this->logger()?->info( // Use info level for successful registration
                '[UUM Provider] Translation namespace registered successfully.',
                ['namespace' => $namespace, 'path' => $langPath]
            );
        } catch (Throwable $e) {
            // If loadTranslationsFrom fails for any reason (e.g., filesystem issues)
            $errorMessage = '[UUM Provider] Failed to register translations using loadTranslationsFrom.';
            $context = [
                'namespace' => $namespace,
                'path' => $langPath,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ];

            // Attempt to handle the error via UEM first, fallback to internal logger
            $this->errorManager()?->handle('UUM_TRANSLATION_LOAD_FAILED', $context, $e)
                ?? $this->logger()?->error($errorMessage, $context); // Use error level for failures
        }
    }

    /**
     * Define the package's publishable resources.
     * Groups resources under specific tags for selective publishing.
     *
     * @return void
     * @sideEffect Makes package resources available for `php artisan vendor:publish`.
     */
    protected function definePublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish Config File
            $this->publishes([
                $this->packageBasePath . '/config/upload-manager.php' => $this->app->configPath('upload-manager.php'),
                // Publish AllowedFileType config as well, maybe with a more specific name?
                $this->packageBasePath . '/config/AllowedFileType.php' => $this->app->configPath('AllowedFileType_UUM.php'),
                 // Publish potentially conflicting configs with specific names
                 $this->packageBasePath . '/config/logging.php' => $this->app->configPath('logging_uum.php'),
                 $this->packageBasePath . '/config/queue.php' => $this->app->configPath('queue_uum.php'),
                 $this->packageBasePath . '/config/filesystems.php' => $this->app->configPath('filesystems_uum.php'),
            ], 'uum-config'); // Tag: uum-config

            // Publish Views
            $this->publishes([
                $this->packageBasePath . '/resources/views' => resource_path('views/vendor/uploadmanager'),
            ], 'uum-views'); // Tag: uum-views

            // Publish Translations
            $this->publishes([
                $this->packageBasePath . '/resources/lang' => $this->app->langPath('vendor/uploadmanager'),
            ], 'uum-translations'); // Tag: uum-translations

            // Consider publishing Migrations if UUM needs its own tables later
            // $this->publishes([
            //     $this->packageBasePath . '/database/migrations/' => $this->app->databasePath('migrations'),
            // ], 'uum-migrations');

             // Publish frontend assets (example for compiled assets, adapt if source needed)
             // $this->publishes([
             //     $this->packageBasePath . '/public/build' => public_path('vendor/uploadmanager'),
             // ], 'uum-assets');

            $this->logger()?->debug('[UUM] Publishable resources defined.');
        }
    }

    /**
     * Register console commands and schedule the temp file cleanup job.
     * Only active when running in the console.
     *
     * @return void
     * @sideEffect Registers console commands and schedules a recurring job.
     * @see \Ultra\UploadManager\Console\UltraSetupCommand
     * @see \Ultra\UploadManager\Console\CleanTempFilesCommand
     * @see \Illuminate\Console\Scheduling\Schedule
     */
    protected function registerCommandsAndScheduling(): void
    {
        if ($this->app->runningInConsole()) {
            // Register console commands provided by the package.
            $this->commands([
                UltraSetupCommand::class, // Setup command for UUM specific tasks (if any)
                CleanTempFilesCommand::class, // Command to clean temporary upload files
            ]);
            $this->logger()?->debug('[UUM] Console commands registered.');

            // Schedule the temporary file cleanup job.
            $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
                // Run daily at midnight by default. Frequency can be configured.
                $schedule->command('ultra:clean-temp')->dailyAt('00:00');
                $this->logger()?->info('[UUM] Temporary file cleanup job scheduled.', ['frequency' => 'daily@00:00']);
                // Example for different frequency:
                // $schedule->command('ultra:clean-temp', ['--hours' => 6])->everySixHours();
            });
        }
    }

    /**
     * Register the Echo broadcast channel for real-time updates.
     * Uses Laravel's standard Broadcast facade.
     *
     * @return void
     * @sideEffect Registers the 'upload' broadcast channel.
     */
    protected function registerEchoChannel(): void
    {
        // Ensure this runs after the main BroadcastServiceProvider has booted.
         $this->app->booted(function () {
            try {
                Broadcast::channel('upload', function ($user = null) { // Allow anonymous access initially
                    // Basic authorization: return true for now.
                    // Implement proper authorization based on user context if needed later.
                    $userId = $user->id ?? 'anonymous';
                    $this->logger()?->debug('[UUM] Authorization check for broadcast channel.', ['channel' => 'upload', 'user_id' => $userId]);
                    return true; // Or implement logic: return $user != null; etc.
                });
                $this->logger()?->debug('[UUM] Broadcast channel registered.', ['channel' => 'upload']);
            } catch (Throwable $e) {
                 $errorMessage = '[UUM] Failed to register broadcast channel.';
                 $context = ['channel' => 'upload', 'exception' => $e->getMessage()];
                 $this->errorManager()?->handle('UUM_BROADCAST_CHANNEL_FAILED', $context, $e)
                    ?? $this->logger()?->error($errorMessage, $context);
            }
         });
    }

    // Note: Redundant publishConfig, publishLoggingConfig methods removed
    // as their logic is now included in definePublishing().
    // Note: Redundant loadTranslation method removed as logic is in registerPackageTranslations().

} // End class UploadManagerServiceProvider