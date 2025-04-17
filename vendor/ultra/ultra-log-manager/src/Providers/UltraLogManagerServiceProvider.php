<?php

declare(strict_types=1);

namespace Ultra\UltraLogManager\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Ultra\UltraLogManager\UltraLogManager;

/**
 * 🎯 UltraLogManagerServiceProvider – Oracoded Logging Service Registration
 *
 * Registers the UltraLogManager service in the Laravel application container,
 * providing a configurable, injectable logging solution for the Ultra ecosystem.
 *
 * 🧱 Structure:
 * - Registers UltraLogManager as a singleton
 * - Configures Monolog as the underlying logger
 * - Publishes configuration for customization
 *
 * 📡 Communicates:
 * - With Laravel container to bind UltraLogManager
 * - With filesystem to publish config
 *
 * 🧪 Testable:
 * - Dependencies injectable via $app
 * - Configuration mockable
 */
class UltraLogManagerServiceProvider extends ServiceProvider
{
    /**
     * 🎯 Register UltraLogManager service
     *
     * Binds UltraLogManager to the Laravel container as a singleton, initializing
     * it with a Monolog logger and configuration.
     *
     * 🧱 Structure:
     * - Merges default config with application config
     * - Creates Monolog logger with StreamHandler
     * - Instantiates UltraLogManager with dependencies
     *
     * 📡 Communicates:
     * - Registers service in $app
     *
     * 🧪 Testable:
     * - $app mockable
     * - Config injectable
     *
     * @return void
     */
    public function register(): void
    {
        // Merge default configuration with application overrides
        $this->mergeConfigFrom(__DIR__ . '/../../config/ultra_log_manager.php', 'ultra_log_manager');

        // Register UltraLogManager as a singleton
        $this->app->singleton(UltraLogManager::class, function (Application $app) {
            $config = $app['config']['ultra_log_manager'] ?? [];

            // Initialize Monolog logger
            $logger = new Logger('ultra_log_manager');
            $logger->pushHandler(new StreamHandler(
                storage_path('logs/ultra_log_manager.log'),
                $config['log_level'] ?? Logger::DEBUG // Configurabile via config
            ));

            // Return UltraLogManager with injected dependencies
            return new UltraLogManager($logger, $config);
        });
    }

    /**
     * 🎯 Bootstrap configuration publishing
     *
     * Publishes the UltraLogManager configuration file to the application’s config directory,
     * allowing customization by end users.
     *
     * 🧱 Structure:
     * - Defines publishable assets with tag
     *
     * 📡 Communicates:
     * - With filesystem to copy config file
     *
     * 🧪 Testable:
     * - Publishing testable in CLI context
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/ultra_log_manager.php' => config_path('ultra_log_manager.php'),
        ], 'ultra-log-config');
    }
}