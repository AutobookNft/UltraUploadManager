<?php

/**
 * Service Provider for the UltraConfigManager (UCM) package.
 *
 * Registers UCM services, configuration, routes, middleware, commands, and
 * publishes necessary resources within the Laravel application lifecycle.
 * This provider acts as the central integration point for the UCM package.
 *
 * --- Structure ---
 * - `register()`: Binds core services (Manager, DAO) to the IoC container and merges config.
 * - `boot()`: Loads resources (translations, views), registers routes/middleware,
 *             publishes assets, and registers console commands. Includes conditional boot logic.
 * - Helper methods: `shouldSkipBoot`, `loadRoutes`, `registerMiddleware`, `publishResources`, `publishMigrations`.
 *
 * --- Usage ---
 * Automatically loaded by Laravel's service provider discovery or explicitly registered
 * in `config/app.php` (Laravel <= 10) or `bootstrap/app.php` (Laravel 11+).
 * No direct manual instantiation is typically needed.
 *
 * --- State ---
 * This provider is stateless itself but modifies the application state by interacting
 * with the IoC Container (`$this->app`), configuration, router, middleware registry,
 * console kernel, and filesystem (for resource loading/publishing).
 *
 * --- Key Methods ---
 * - `register()`: Essential for binding the core UCM services.
 * - `boot()`: Handles integration aspects like resources, routes, commands.
 * - `publishResources()`: Defines assets made available via `vendor:publish`.
 *
 * --- Signals & Side Effects ---
 * - Publishes assets under the 'uconfig-resources' tag.
 * - Registers the console command `uconfig:initialize`.
 * - Registers the middleware alias `uconfig.check_role`.
 * - Modifies IoC container bindings (registers singletons for 'uconfig', ConfigDaoInterface).
 * - Merges package configuration into the application's config.
 * - Loads namespaced views and translations.
 * - Registers routes (conditionally) and middleware.
 *
 * --- Privacy (GDPR) Considerations ---
 * This Service Provider itself does not directly handle PII. However, it registers
 * services (Manager, DAO) and publishes resources (migrations for audit tables)
 * that *are* involved in handling potentially sensitive configuration data and user IDs
 * for auditing purposes. Compliance is therefore delegated to the components it
 * registers/enables and how the application uses them.
 * `@privacy-delegated`: Responsibility lies with registered services and published components.
 *
 * --- Dependencies ---
 * - Relies on the Laravel Application container (`$this->app`) for service resolution and binding.
 * - Interacts with `Illuminate\Routing\Router` for routes and middleware.
 * - Requires filesystem access for resource loading/publishing.
 * - Depends on various internal UCM components (DAO, Manager, Services, Constants, Middleware, Command).
 * - May implicitly rely on `Ultra\UltraLogManager` or `Ultra\ErrorManager` if used by the Manager/DAO.
 *
 * --- Testing Strategy ---
 * Primarily tested via integration tests simulating the Laravel boot process. Assertions focus on:
 *   - Successful resolution of bound services (`app->make('uconfig')`).
 *   - Availability of registered routes (`route:list`).
 *   - Correct registration of middleware aliases.
 *   - Correct registration of console commands (`artisan list`).
 *   - Successful execution of `vendor:publish --tag=uconfig-resources`.
 *   - Successful execution of `migrate` with published migrations.
 *
 * --- Internal Logic Highlights ---
 * - Uses singleton bindings for performance.
 * - Binds DAO interface to a concrete implementation (Eloquent) for flexibility.
 * - Implements `shouldSkipBoot()` to prevent issues in queue workers.
 * - Orders migration publishing (`publishMigrations`) carefully for foreign keys.
 * - Uses `$app->booted()` for reliable route loading.
 * - Applies 'web' middleware group to UCM routes.
 *
 * @package     Ultra\UltraConfigManager\Providers
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.0.4 // Adopted PHPDoc Standard with Oracode Detail preservation.
 * @since       1.0.0
 *
 * @see \Ultra\UltraConfigManager\UltraConfigManager Core service class.
 * @see \Ultra\UltraConfigManager\Contracts\ConfigDaoInterface DAO contract.
 * @see \Ultra\UltraConfigManager\Dao\EloquentConfigDao Default DAO implementation.
 * @see \Ultra\UltraConfigManager\Console\Commands\UConfigInitializeCommand Registered command.
 * @see \Ultra\UltraConfigManager\Http\Middleware\CheckConfigManagerRole Registered middleware.
 */

namespace Ultra\UltraConfigManager\Providers;

// Laravel Contracts & Facades
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface; // PSR standard

// UCM Components
use Ultra\UltraConfigManager\Console\Commands\UConfigInitializeCommand;
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface; // Use Contract namespace
use Ultra\UltraConfigManager\Dao\EloquentConfigDao;
use Ultra\UltraConfigManager\Http\Middleware\CheckConfigManagerRole;
use Ultra\UltraConfigManager\Services\VersionManager;
use Ultra\UltraConfigManager\UltraConfigManager;

class UConfigServiceProvider extends ServiceProvider
{
    /**
     * Register the package's core services in the container.
     *
     * Binds the UltraConfigManager service (aliased to 'uconfig') and the ConfigDaoInterface
     * as singletons. Merges the default package configuration ('uconfig'). This method ensures
     * that the main UCM service is ready and configured correctly within the application
     * during the 'register' phase of the Laravel lifecycle.
     *
     * --- Core Logic ---
     * 1.  **Singleton Binding ('uconfig'):** Registers `UltraConfigManager` as a singleton.
     * 2.  **Dependency Resolution:** Resolves all object dependencies (`ConfigDaoInterface`,
     *     `VersionManager`, `GlobalConstants`, `CacheRepository`, `LoggerInterface`)
     *     using the Laravel service container (`$app->make()`).
     * 3.  **Scalar Configuration:** Reads specific config values (`cache.ttl`, `cache.enabled`,
     *     `load_on_init`) from the 'uconfig' file.
     * 4.  **Instantiation:** Creates the `UltraConfigManager` instance with correctly ordered dependencies.
     * 5.  **DAO Interface Binding:** Binds `ConfigDaoInterface` to `EloquentConfigDao` as a singleton.
     * 6.  **Configuration Merging:** Merges the package's default config settings.
     * --- End Core Logic ---
     *
     * @return void
     * @sideEffect Modifies the IoC container: Binds 'uconfig' singleton, binds ConfigDaoInterface singleton.
     * @sideEffect Modifies application config: Merges 'uconfig' configuration defaults.
     * @see \Ultra\UltraConfigManager\UltraConfigManager::__construct() For constructor details.
     * @see \Ultra\UltraConfigManager\Contracts\ConfigDaoInterface Dependency Interface.
     * @see \Ultra\UltraConfigManager\Dao\EloquentConfigDao Bound Implementation.
     * @see \Ultra\UltraConfigManager\Services\VersionManager Resolved Dependency.
     * @see \Ultra\UltraConfigManager\Constants\GlobalConstants Resolved Dependency.
     * @see \Illuminate\Contracts\Cache\Repository Resolved Dependency.
     * @see \Psr\Log\LoggerInterface Resolved Dependency.
     * @configReads uconfig.cache.ttl Defines the cache Time-To-Live (default: 3600).
     * @configReads uconfig.cache.enabled Enables/disables UCM caching (default: true).
     * @configReads uconfig.load_on_init Determines if config should be preloaded (default: true).
     */
    public function register(): void
    {
        // Bind the main UltraConfigManager service as a singleton, aliased to 'uconfig'.
        $this->app->singleton('uconfig', function (Application $app) {
            // Resolve object dependencies from the container.
            $configDao = $app->make(ConfigDaoInterface::class);
            $versionManager = $app->make(VersionManager::class);
            $cacheRepository = $app->make(CacheRepository::class);
            $logger = $app->make(LoggerInterface::class); // Resolves default logger

            // Retrieve scalar configuration values.
            $cacheTtl = $app['config']->get('uconfig.cache.ttl', 3600);
            $cacheEnabled = $app['config']->get('uconfig.cache.enabled', true);
            $loadOnInit = $app['config']->get('uconfig.load_on_init', true); // Ensure this key exists in config

            // Instantiate with correctly ordered dependencies.
            return new UltraConfigManager(
                $configDao, $versionManager, $cacheRepository, $logger,
                $cacheTtl, $cacheEnabled, $loadOnInit
            );
        }); // End 'uconfig' singleton

        // Bind the ConfigDaoInterface to the Eloquent implementation as a singleton.
        $this->app->singleton(
            ConfigDaoInterface::class,
            EloquentConfigDao::class // Assuming Ultra\UltraConfigManager\Dao\EloquentConfigDao
        );

        // Merge the default package configuration.
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/uconfig.php',
            'uconfig'
        );
    }

    /**
     * Bootstrap package services and resources after registration.
     *
     * Performs tasks during the 'boot' phase: loads resources (translations, views),
     * registers routes (if published), middleware alias, console commands, and defines
     * publishable assets. Skips booting in queue worker contexts.
     *
     * --- Core Logic ---
     * 1. Checks if booting should be skipped (e.g., via `shouldSkipBoot()`).
     * 2. Loads namespaced translations ('uconfig') from the package's lang directory.
     * 3. Loads namespaced views ('uconfig') from the package's views directory.
     * 4. Registers package routes using `loadRoutes()`.
     * 5. Registers the 'uconfig.check_role' middleware alias using `registerMiddleware()`.
     * 6. If running in the console:
     *    - Defines publishable resources using `publishResources()`.
     *    - Registers the `UConfigInitializeCommand` console command.
     * --- End Core Logic ---
     *
     * @return void
     * @sideEffect Loads translation/view files into Laravel's resolvers.
     * @sideEffect Registers routes (conditionally), a middleware alias, and a console command.
     * @sideEffect Defines publishable assets for `vendor:publish`.
     * @see self::shouldSkipBoot()
     * @see self::loadTranslationsFrom() Provided by Illuminate\Support\ServiceProvider
     * @see self::loadViewsFrom() Provided by Illuminate\Support\ServiceProvider
     * @see self::loadRoutes()
     * @see self::registerMiddleware()
     * @see self::publishResources()
     * @see self::commands() Provided by Illuminate\Support\ServiceProvider
     * @see \Ultra\UltraConfigManager\Console\Commands\UConfigInitializeCommand
     */
    public function boot(): void
    {
        // Skip booting for queue workers.
        if ($this->shouldSkipBoot()) {
            return;
        }

        // Load package translations and views, namespaced to 'uconfig'.
        $this->loadTranslationsFrom(dirname(__DIR__, 2) . '/resources/lang', 'uconfig');
        $this->loadViewsFrom(dirname(__DIR__, 2) . '/resources/views', 'uconfig');

        // Load routes from the published location and register middleware alias.
        $this->loadRoutes();
        $this->registerMiddleware();

        // Register commands and publishing tasks only when running in the console.
        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->commands([
                UConfigInitializeCommand::class,
            ]);
        }
    }

    /**
     * Determine if the boot logic should be skipped (e.g., in queue workers).
     *
     * --- Core Logic ---
     * Checks the second command-line argument (`$_SERVER['argv'][1]`) against a hardcoded
     * list of common Laravel queue commands ('queue:work', 'queue:listen').
     * --- End Core Logic ---
     *
     * @internal This method relies on `$_SERVER` and might be fragile. Consider alternatives if needed.
     * @return bool True if booting should be skipped based on the command line argument.
     */
    protected function shouldSkipBoot(): bool
    {
        $arg = $_SERVER['argv'][1] ?? null;
        return in_array($arg, ['queue:work', 'queue:listen'], true);
    }

    /**
     * Load the package routes file if it has been published by the user.
     *
     * --- Core Logic ---
     * 1. Waits for the application to be fully booted (`$app->booted()`).
     * 2. Checks if the routes file exists at `base_path('routes/uconfig.php')`.
     * 3. If it exists, resolves the Router instance.
     * 4. Groups the routes defined in the file under the 'web' middleware group.
     * --- End Core Logic ---
     *
     * @return void
     * @sideEffect Registers routes from `base_path('routes/uconfig.php')` if the file exists.
     */
    protected function loadRoutes(): void
    {
        $this->app->booted(function () {
            /** @var Router $router */
            $router = $this->app->make(Router::class);
            $routesPath = base_path('routes/uconfig.php'); // Expected published location

            if (file_exists($routesPath)) {
                $router->middleware(['web']) // Ensure session, CSRF, etc.
                       ->group($routesPath);
            }
        });
    }

    /**
     * Register the middleware alias for UCM role/permission checking.
     *
     * --- Core Logic ---
     * Resolves the Router instance from the container and calls `aliasMiddleware`
     * to map the string 'uconfig.check_role' to the `CheckConfigManagerRole` class.
     * --- End Core Logic ---
     *
     * @return void
     * @sideEffect Modifies the router's alias middleware registry.
     * @see \Ultra\UltraConfigManager\Http\Middleware\CheckConfigManagerRole
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router']; // Access router via container
        $router->aliasMiddleware('uconfig.check_role', CheckConfigManagerRole::class);
    }

    /**
     * Define the resources that are publishable via `php artisan vendor:publish`.
     *
     * --- Core Logic ---
     * 1. Defines paths for config, views, translations, routes, and a seeder stub.
     * 2. Groups all these resources under the 'uconfig-resources' tag.
     * 3. Calls `publishMigrations()` separately to handle ordered migration publishing.
     * --- End Core Logic ---
     *
     * @internal Warning: Publishing the seeder stub might overwrite an existing user file.
     * @return void
     * @sideEffect Makes package resources available for publishing.
     * @see self::publishMigrations()
     */
    protected function publishResources(): void
    {
        $timestamp = now()->format('Y_m_d_His_u');
        $baseDir = dirname(__DIR__, 2); // Package root

        // Publish migrations first (ordered).
        $this->publishMigrations($timestamp, $baseDir);

        // Publish other resources under the main tag.
        $this->publishes([
            $baseDir . '/config/uconfig.php' => $this->app->configPath('uconfig.php'),
            $baseDir . '/resources/views' => resource_path('views/vendor/uconfig'),
            $baseDir . '/resources/lang' => $this->app->langPath('vendor/uconfig'),
            // Publish the routes file stub to the application's routes directory
            $baseDir . '/routes/web.php' => base_path('routes/uconfig.php'), // Source was routes/web.php in package
            // Optional Seeder Stub
            $baseDir . '/database/seeders/stubs/PermissionSeeder.php.stub' => $this->app->databasePath("seeders/PermissionSeeder.php"),
        ], 'uconfig-resources'); // Group tag
    }

    /**
     * Define the package's database migrations as publishable, ensuring correct execution order.
     *
     * --- Core Logic ---
     * 1. Defines an array mapping destination migration filenames (with order prefix)
     *    to the source stub filenames within the package.
     * 2. Iterates through this array.
     * 3. For each migration, calls `$this->publishes()` to make the stub file publishable
     *    to the application's `database/migrations` directory. The destination filename
     *    includes the generated timestamp and the numeric order prefix.
     * --- End Core Logic ---
     *
     * @param string $timestamp Unique timestamp string for migration filenames.
     * @param string $baseDir   Package root directory path.
     * @return void
     * @sideEffect Makes migration files available for publishing under the 'uconfig-resources' tag.
     * @internal The order defined in the $migrations array is critical for foreign keys.
     */
    protected function publishMigrations(string $timestamp, string $baseDir): void
    {
        // Define migrations in the required execution order.
        $migrations = [
            '0_create_uconfig_table.php'          => 'create_uconfig_table.php.stub',
            '1_create_uconfig_versions_table.php' => 'create_uconfig_versions_table.php.stub',
            '2_create_uconfig_audit_table.php'    => 'create_uconfig_audit_table.php.stub',
        ];

        foreach ($migrations as $orderedName => $stubFilename) {
            $this->publishes([
                // Source: Stub file within the package's migrations directory
                $baseDir . "/database/migrations/{$stubFilename}" =>
                // Destination: Application's migrations directory with timestamp and order prefix
                    $this->app->databasePath("migrations/{$timestamp}_{$orderedName}"),
            ], 'uconfig-resources'); // Publish under the main group tag
        }
    }
} // End class UConfigServiceProvider