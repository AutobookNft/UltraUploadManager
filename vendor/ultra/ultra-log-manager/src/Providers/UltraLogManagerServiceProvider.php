<?php

declare(strict_types=1);

namespace Ultra\UltraLogManager\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Ultra\UltraLogManager\Contracts\ContextSanitizerInterface;
use Ultra\UltraLogManager\Sanitizer\NoOpSanitizer;
use Ultra\UltraLogManager\UltraLogManager;

/**
 * UltraLogManagerServiceProvider – DI bindings & config publishing.
 *
 * This provider wires UltraLogManager into a Laravel application and offers a
 * default (no‑op) implementation of {@see ContextSanitizerInterface}. It also
 * publishes the configuration file so that host apps may override channels,
 * log levels or swap the sanitizer binding.
 *
 * --- Core Logic ---
 * 1. Merge package config with app‑level overrides.
 * 2. Bind {@see ContextSanitizerInterface} → {@see NoOpSanitizer} (singleton).
 * 3. Register {@see UltraLogManager} as singleton using Monolog under the
 *    channel name defined in config `ultra_log_manager.log_channel`.
 *    ⚠️  Monolog v3 deprecates numeric level constants; we ingest a **PSR‑3
 *    string level** (e.g. "debug") and convert via {@see Logger::toMonologLevel()}.
 * 4. Publish `config/ultra_log_manager.php` for artisan vendor:publish.
 * --- End Core Logic ---
 *
 * @package     Ultra\UltraLogManager\Providers
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @license     MIT
 * @version     1.0.1‑oracode
 */
final class UltraLogManagerServiceProvider extends ServiceProvider
{
    /** @inheritDoc */
    public function register(): void
    {
        // 1) Merge default package configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/ultra_log_manager.php', 'ultra_log_manager');

        // 2) Default sanitizer → no‑op (can be swapped by host app)
        $this->app->singleton(ContextSanitizerInterface::class, NoOpSanitizer::class);

        // 3) Logger singleton
        $this->app->singleton(UltraLogManager::class, function (Application $app): UltraLogManager {
            $cfg = $app['config']['ultra_log_manager'] ?? [];

            $channel  = $cfg['log_channel'] ?? 'ultra_log_manager';
            $logPath  = storage_path("logs/{$channel}.log");
            $psrLevel = $cfg['log_level'] ?? LogLevel::DEBUG; // PSR‑3 string (e.g. 'debug')
            $monoLevel = Logger::toMonologLevel($psrLevel);   // convert to Monolog int

            $mono = new Logger($channel);
            $mono->pushHandler(new StreamHandler($logPath, $monoLevel));

            return new UltraLogManager($mono, $cfg);
        });
    }

    /** @inheritDoc */
    public function boot(): void
    {
        // 4) Allow artisan vendor:publish --tag=ultra-log-config
        $this->publishes([
            __DIR__ . '/../../config/ultra_log_manager.php' => config_path('ultra_log_manager.php'),
        ], 'ultra-log-config');
    }
}
