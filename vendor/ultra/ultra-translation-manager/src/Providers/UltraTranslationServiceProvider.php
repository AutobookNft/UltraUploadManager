<?php

declare(strict_types=1);

namespace Ultra\TranslationManager\Providers;

// Core Laravel contracts and classes
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator as LaravelTranslator;

// PSR standard interface (dependency for DefaultLogger/DefaultErrorReporter)
use Psr\Log\LoggerInterface as PsrLoggerInterface;

// UTM specific classes and interfaces
use Ultra\TranslationManager\ErrorReporters\DefaultErrorReporter;
use Ultra\TranslationManager\Interfaces\ErrorReporter as UtmErrorReporterInterface;
use Ultra\TranslationManager\Interfaces\LoggerInterface as UtmLoggerInterface;
use Ultra\TranslationManager\Loggers\DefaultLogger; // Ora l'unica implementazione interna
use Ultra\TranslationManager\TranslationManager;

// Non più necessario: use Ultra\UltraLogManager\UltraLogManager;

/**
 * 🎯 Service Provider for Ultra Translation Manager (UTM) – Oracoded Standalone Refactored
 *
 * Registers and bootstraps the Facade-free and **ULM-Independent** Ultra Translation
 * Manager services within the Laravel application. Ensures proper dependency
 * injection using standard Laravel/PSR components.
 *
 * 🧱 Structure:
 * - Merges default package configuration.
 * - Registers singleton bindings for UTM components (DefaultLogger, DefaultErrorReporter),
 *   injecting the standard PSR-3 application logger.
 * - Registers the main TranslationManager singleton ('ultra.translation'), resolving all its dependencies.
 * - Extends Laravel's core 'translator' service to use the UTM implementation.
 * - Binds TranslatorContract to the UTM implementation.
 * - Publishes configuration file in the boot method.
 * - Registers core package translations in the boot method using the resolved UTM instance.
 *
 * 📡 Communicates:
 * - With Laravel's IoC container ($app) to resolve dependencies (PSR Logger, Cache, FS, App) and register services.
 * - With the Filesystem during publishing in boot().
 * - Does NOT interact with or depend on UltraLogManagerServiceProvider.
 *
 * 🧪 Testable:
 * - Service registration logic testable via the Application container.
 * - Boot logic involves filesystem interaction and container resolution.
 * - No static dependencies. Independent of ULM.
 */
final class UltraTranslationServiceProvider extends ServiceProvider
{
    /**
     * 🎯 Register UTM services (Standalone Mode).
     * Binds UTM components and the main TranslationManager, using the standard
     * application PSR-3 logger instead of ULM.
     *
     * @return void
     */
    public function register(): void
    {
        // 1. Merge Configuration (invariato)
        $configPath = __DIR__ . '/../../config/translation-manager.php';
        $configKey = 'translation-manager';
        $this->mergeConfigFrom($configPath, $configKey);

        // 2. Register UTM Logger (DefaultLogger - Standalone)
        // Ora dipende dal logger PSR-3 standard dell'applicazione.
        $this->app->singleton(DefaultLogger::class, function (Application $app) {
            // Resolve the standard PSR-3 logger instance from the container
            $psrLogger = $app->make(PsrLoggerInterface::class);
            // Instantiate DefaultLogger with the resolved PSR-3 logger
            return new DefaultLogger($psrLogger);
        });
        // Bind the UTM-specific interface to the concrete implementation
        $this->app->bind(UtmLoggerInterface::class, DefaultLogger::class);

        // 3. Register UTM Error Reporter (DefaultErrorReporter - Standalone)
        // Ora dipende anche dal logger PSR-3 standard.
        $this->app->singleton(DefaultErrorReporter::class, function (Application $app) {
            // Resolve the standard PSR-3 logger instance from the container
            $psrLogger = $app->make(PsrLoggerInterface::class);
            // Instantiate DefaultErrorReporter with the resolved PSR-3 logger
            return new DefaultErrorReporter($psrLogger);
        });
        // Bind the UTM-specific interface to the concrete implementation
        $this->app->bind(UtmErrorReporterInterface::class, DefaultErrorReporter::class);

        // 4. Register Main TranslationManager ('ultra.translation') (invariato nella logica, ma le dipendenze risolte sono ora standalone)
        $this->app->singleton('ultra.translation', function (Application $app) use ($configKey) {
            $config = $app['config'][$configKey] ?? [];

            // Resolve all dependencies required by TranslationManager's constructor
            $laravelApp = $app->make(Application::class);
            $cacheFactory = $app->make(CacheFactory::class);
            $filesystem = $app->make(Filesystem::class);
            // Queste interfacce ora risolvono DefaultLogger/DefaultErrorReporter che usano PSR-3
            $utmLogger = $app->make(UtmLoggerInterface::class);
            $utmErrorReporter = $app->make(UtmErrorReporterInterface::class);

            // Instantiate the facade-free, standalone TranslationManager
            return new TranslationManager(
                $laravelApp,
                $cacheFactory,
                $filesystem,
                $utmLogger,
                $utmErrorReporter,
                $config
            );
        });

        // 5. Extend Laravel's Core Translator (invariato)
        $this->app->extend('translator', function (LaravelTranslator $laravelTranslator, Application $app) {
            $utmInstance = $app->make('ultra.translation');
            $utmInstance->setLaravelTranslator($laravelTranslator);
            return $utmInstance;
        });

        // 6. Bind TranslatorContract Interface (invariato)
        $this->app->singleton(TranslatorContract::class, function (Application $app) {
            return $app->make('translator');
        });
    }

    /**
     * 🎯 Bootstrap UTM services (Standalone Mode).
     * Handles publishing and initial translation registration without Facades or ULM dependency.
     *
     * @return void
     */
    public function boot(): void
    {
        // 1. Publish Configuration File (invariato)
        $configPath = __DIR__ . '/../../config/translation-manager.php';
        $publishPath = $this->app->configPath('translation-manager.php');
        $publishTag = 'utm-config';
        $this->publishes([$configPath => $publishPath], $publishTag);

        // 2. Register Core Package Translations (Standalone Mode - Facade-Free)
        if ($this->app->runningInConsole() || !$this->app->configurationIsCached()) {
            try {
                /** @var TranslationManager $utmInstance */
                $utmInstance = $this->app->make('ultra.translation'); // Risolve l'istanza UTM

                $coreTranslationsPath = __DIR__ . '/../../resources/lang';

                if ($this->app->make(Filesystem::class)->isDirectory($coreTranslationsPath)) {
                    $utmInstance->registerPackageTranslations(
                        'core',
                        $coreTranslationsPath
                    );
                } else {
                     // Log using the resolved standard logger via the UTM interface if path not found
                     $this->app->make(UtmLoggerInterface::class)->warning(
                         "[UTM Boot] Core translations path not found or invalid: {$coreTranslationsPath}"
                     );
                 }
            } catch (\Exception $e) {
                 // Catch potential errors during container resolution or registration in boot
                 try {
                     // Attempt to log the error using the standard logger if possible
                     $this->app->make(PsrLoggerInterface::class)->error(
                         "[UTM Boot] Failed to register core translations: " . $e->getMessage(),
                         ['exception' => $e]
                     );
                 } catch (\Exception $logError) {
                     // If even logging fails, there's not much more we can do here in boot
                 }
            }
        }
    }
}