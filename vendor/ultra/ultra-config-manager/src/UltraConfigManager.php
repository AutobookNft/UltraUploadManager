<?php

/**
 * 📜 Oracode Class: UltraConfigManager
 *
 * @package         Ultra\UltraConfigManager
 * @version         1.3.0 // Oracode v1.5.0 refactor + getOrFail method + improved get() logic
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 * @since           1.0.0
 */

namespace Ultra\UltraConfigManager;

// Laravel & PHP Dependencies
use Illuminate\Contracts\Auth\Authenticatable; // Keep if using Auth::id() contextually, though not directly used here
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Arr; // For array dot notation access
use Psr\Log\LoggerInterface;
use Throwable;
use InvalidArgumentException; // Standard PHP Exception
use RuntimeException;       // Standard PHP Exception

// Ultra Ecosystem Dependencies & Local Components
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigAuditData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigDisplayData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigEditData;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException; // Specific exception
use Ultra\UltraConfigManager\Exceptions\PersistenceException;    // Specific exception
use Ultra\UltraConfigManager\Services\VersionManager;

// Removed: use Ultra\ErrorManager\Facades\UltraError; (Not used directly)

/**
 * 🎯 Purpose: Central orchestrator for managing application configuration within the Ultra ecosystem.
 *    Provides a unified API for retrieving, setting, and deleting configuration values,
 *    while ensuring data integrity, security (encryption via DAO/Model), versioning, auditing,
 *    and cache consistency. It acts as the primary programmatic interface to UCM.
 *
 * 🧱 Structure:
 *    - Holds configuration state in memory (`$config` array).
 *    - Interacts with persistent storage via `ConfigDaoInterface`.
 *    - Manages configuration versions using `VersionManager`.
 *    - Interacts with Laravel's cache via `CacheRepository`.
 *    - Logs activities using `LoggerInterface`.
 *    - Provides public API methods (`get`, `getOrFail`, `set`, `delete`, `has`, `all`, `reload`, `validateConstant`, `getAllEntriesForDisplay`, `findEntryForEdit`, `findEntryForAudit`).
 *    - Internal methods handle loading (`loadConfig`, `loadFromDatabase`, `mergeWithEnvironment`) and cache management (`isCacheEnabled`, `getCacheTtl`, `refreshConfigCache`).
 *
 * 🧩 Context: Instantiated typically as a singleton by `UConfigServiceProvider`. Used throughout
 *    the application (directly or via the `UConfig` Facade) wherever configuration access is needed.
 *    Operates within a Laravel application context, relying on injected Cache and Logger.
 *
 * 🛠️ Usage:
 *    `UConfig::get('key', 'default')` // Get value or default
 *    `UConfig::getOrFail('key')`      // Get required value or throw exception
 *    `UConfig::set('key', 'value', 'category', Auth::id())`
 *    `UConfig::delete('key', Auth::id())`
 *    `UConfig::all()` // Get all config values
 *    `UConfig::reload()` // Force reload from source
 *    `$manager->getAllEntriesForDisplay()` // Get DTOs for UI listing
 *    `$manager->findEntryForEdit($id)`     // Get DTO for edit form
 *    `$manager->findEntryForAudit($id)`    // Get DTO for audit trail view
 *
 * 💾 State:
 *    - `$config`: In-memory cache of key => {value, category}. Loaded on init or reload.
 *    - `$cacheRepository`: Laravel Cache Repository instance (external state).
 *    - Database State: Managed via injected `configDao`.
 *
 * 🗝️ Key Methods:
 *    - `get`, `getOrFail`: Core read operations (optional vs required).
 *    - `set`, `delete`: Core write/delete operations.
 *    - `has`, `all`: Read operations on in-memory state.
 *    - `loadConfig`, `reload`: State hydration management.
 *    - `refreshConfigCache`: External cache synchronization.
 *    - `validateConstant`: Constant validation helper (uses internal GlobalConstants).
 *    - `getAllEntriesForDisplay`, `findEntryForEdit`, `findEntryForAudit`: Data retrieval for UI/DTOs.
 *
 * 🚦 Signals:
 *    - Returns: values, DTOs, Collections, Paginators, booleans.
 *    - Throws: `InvalidArgumentException`, `ConfigNotFoundException`, `PersistenceException`, `RuntimeException`.
 *    - Logs: Actions and errors via injected `LoggerInterface`.
 *    - Cache: Interacts with cache store via `CacheRepository` using `CACHE_KEY`.
 *    - Database: Triggers read/write/delete operations and audit/version record creation via `configDao`.
 *
 * 🛡️ Privacy (GDPR):
 *    - Manages potentially sensitive configuration data. Encryption at rest is handled by the `UltraConfigModel` (delegated).
 *    - `@privacy-input`: Accepts optional `$userId` in `set`/`delete` methods for auditing.
 *    - `@privacy-internal`: Passes `$userId` to `configDao`. Assumes DAO/Model handle `userId` appropriately based on application context.
 *    - `@privacy-delegated`: Relies on `UltraConfigModel`'s `EncryptedCast` for value encryption.
 *    - Recommendation: Avoid storing direct PII (Personally Identifiable Information) in configuration values whenever possible. Use references or anonymized data if needed.
 *
 * 🤝 Dependencies:
 *    - `ConfigDaoInterface`: Data Access Object for persistence.
 *    - `VersionManager`: Service for versioning logic (used by DAO).
 *    - `GlobalConstants`: Defines application-wide constants (dependency is implicit via code usage, should ideally be injected if logic grows).
 *    - `CacheRepository`: Laravel Cache contract.
 *    - `LoggerInterface`: PSR-3 Logger contract (ULM).
 *    - `CategoryEnum`: Defines valid configuration categories.
 *    - DTOs (`ConfigDisplayData`, `ConfigEditData`, `ConfigAuditData`): Data Transfer Objects for UI.
 *    - Exceptions (`ConfigNotFoundException`, `PersistenceException`): Custom exceptions.
 *    - (Implicit) Laravel Application Context (for `app()`, `storage_path()`, `config()`, service container).
 *
 * 🧪 Testing:
 *    - Unit Test: Mock all injected dependencies (`configDao`, `versionManager`, `cacheRepository`, `logger`). Test logic of public methods, verify mock interactions and exception throwing.
 *    - Integration Test: Use `RefreshDatabase`. Test against a real DB connection and cache driver (e.g., 'array' or 'database'). Verify state changes in DB, cache, and internal `$config` array. Test interaction between Manager and DAO.
 *
 * 💡 Logic:
 *    - Loading (`loadConfig`): Cache -> DB -> Env (merge). Logs each step. Handles cache/DB read errors gracefully.
 *    - Mutation (`set`/`delete`): Validates input -> DB (via DAO which handles transaction, versioning, audit) -> Update Memory -> Update Cache. Logs steps and errors. Throws on failure.
 *    - Retrieval (`get`/`getOrFail`): Checks Memory -> Returns value/default or throws `ConfigNotFoundException`. Logs access/misses.
 *    - Caching (`refreshConfigCache`): Uses locking to prevent race conditions during full refresh. Updates incrementally on `set`/`delete`. Logs cache operations.
 *    - Validation: Explicit checks for key format, value type, category enum membership.
 *    - UI Methods: Delegate data fetching to DAO, map results to DTOs, handle pagination. Catch persistence errors.
 *
 * @package Ultra\UltraConfigManager
 */
final class UltraConfigManager // Add final if class is not intended to be extended
{
    /**
     * In-memory storage for configuration values.
     * Format: ['key' => ['value' => mixed, 'category' => ?string]]
     * @var array<string, array{value: mixed, category?: string|null}>
     */
    private array $config = [];

    /** @var ConfigDaoInterface The injected DAO for database interactions. */
    protected readonly ConfigDaoInterface $configDao;
    /** @var VersionManager The injected service for version management (used by DAO). */
    protected readonly VersionManager $versionManager;
    // protected readonly GlobalConstants $globalConstants; // Removed if only used statically
    /** @var CacheRepository The injected Laravel cache repository. */
    protected readonly CacheRepository $cacheRepository;
    /** @var LoggerInterface The injected PSR-3 logger instance. */
    protected readonly LoggerInterface $logger;
    /** @var int Cache Time-To-Live in seconds. */
    protected readonly int $cacheTtl;
    /** @var bool Flag indicating if caching is enabled. */
    protected readonly bool $cacheEnabled;

    /** @var string The key used for storing the entire configuration in the cache. */
    private const CACHE_KEY = 'ultra_config.cache';

    /**
     * 🎯 Constructor: Initializes the manager with injected dependencies and loads initial config.
     *
     * @param ConfigDaoInterface $configDao DAO for database operations.
     * @param VersionManager $versionManager Service for versioning.
     * @param CacheRepository $cacheRepository Laravel cache repository.
     * @param LoggerInterface $logger PSR-3 logger instance.
     * @param int $cacheTtl Cache TTL in seconds (from config).
     * @param bool $cacheEnabled Whether caching is enabled (from config).
     * @param bool $loadOnInit Flag to control initial configuration loading (useful for tests).
     */
    public function __construct(
        ConfigDaoInterface $configDao,
        VersionManager $versionManager,
        CacheRepository $cacheRepository,
        LoggerInterface $logger,
        int $cacheTtl = 3600,
        bool $cacheEnabled = true,
        bool $loadOnInit = true
    ) {
        $this->configDao = $configDao;
        $this->versionManager = $versionManager;
        $this->cacheRepository = $cacheRepository;
        $this->logger = $logger;
        $this->cacheTtl = $cacheTtl;
        $this->cacheEnabled = $cacheEnabled;

        $this->logger->info('UCM Lifecycle: Initializing UltraConfigManager.', [
            'cacheEnabled' => $this->cacheEnabled,
            'cacheTtl' => $this->cacheTtl,
        ]);

        if ($loadOnInit) {
            // Check if core services are bound before attempting load
            // Use function_exists('app') for safety outside full Laravel context (e.g., unit tests)
            if (function_exists('app') && (!app()->bound('db') || !app()->bound('cache'))) {
                 $this->logger->warning('UCM Lifecycle: DB or Cache services not bound. Skipping initial config load.', [
                    'db_bound' => function_exists('app') && app()->bound('db'),
                    'cache_bound' => function_exists('app') && app()->bound('cache'),
                 ]);
            } else {
                $this->loadConfig(); // Proceed with loading
            }
        } else {
             $this->logger->info('UCM Lifecycle: Initial config load skipped via constructor flag.');
        }
    }

    /**
     * 🎯 Checks if caching is globally enabled for UCM.
     * @internal Helper method.
     * @return bool True if caching is enabled, false otherwise.
     */
    private function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * 🎯 Gets the configured Cache Time-To-Live (TTL).
     * @internal Helper method.
     * @return int The cache TTL in seconds.
     */
    private function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * 🎯 Loads the configuration into memory.
     * Follows the sequence: Cache -> Database -> Environment Variables (Merge).
     * Stores the final merged configuration in the cache if enabled.
     *
     * @return void
     * @sideEffect Populates the internal `$this->config` array.
     * @sideEffect Writes to the cache store if enabled and load from DB occurs.
     * @sideEffect Logs loading steps and potential cache/DB errors.
     * @see self::loadFromDatabase()
     * @see self::mergeWithEnvironment()
     * @see self::refreshConfigCache() For how cache is written.
     */
    public function loadConfig(): void
    {
        $this->logger->info('UCM Load: Starting configuration load sequence.');
        $useCache = $this->isCacheEnabled();
        $cacheKey = self::CACHE_KEY;

        // 1. Try loading from Cache
        if ($useCache) {
            $this->logger->debug('UCM Load: Cache is enabled. Attempting to load from cache.', ['key' => $cacheKey]);
            try {
                $cachedConfig = $this->cacheRepository->get($cacheKey);
                // Ensure it's a valid array before using it
                if (is_array($cachedConfig)) {
                    $this->config = $cachedConfig;
                    $this->logger->info('UCM Load: Configuration successfully loaded from cache.', ['key' => $cacheKey, 'count' => count($cachedConfig)]);
                    // Configuration loaded from cache, exit early
                    return;
                } else {
                     $this->logger->info('UCM Load: Cache miss or invalid data type in cache.', ['key' => $cacheKey, 'type' => gettype($cachedConfig)]);
                }
            } catch (Throwable $e) {
                // Log cache read errors but proceed to load from DB
                $this->logger->error('UCM Load: Error reading from cache. Proceeding with DB load.', [
                    'key' => $cacheKey, 'exception' => $e::class, 'message' => $e->getMessage(),
                ]);
            }
        } else {
            $this->logger->info('UCM Load: Cache is disabled. Loading directly from primary sources.');
        }

        // 2. Load from Database and Environment (if not loaded from cache)
        $this->logger->info('UCM Load: Loading configuration from database and environment.');
        $dbConfig = $this->loadFromDatabase(); // Fetch from DB via DAO
        $mergedConfig = $this->mergeWithEnvironment($dbConfig); // Merge ENV vars
        $this->config = $mergedConfig; // Update in-memory config
        $this->logger->info('UCM Load: Configuration loaded from DB/Env.', ['db_count' => count($dbConfig), 'merged_count' => count($mergedConfig)]);

        // 3. Store the freshly loaded config in Cache (if enabled)
        if ($useCache) {
            $ttl = $this->getCacheTtl();
            try {
                // Use put instead of add to overwrite potentially stale cache
                $success = $this->cacheRepository->put($cacheKey, $this->config, $ttl);
                if ($success) {
                    $this->logger->info('UCM Load: Fresh configuration stored in cache.', ['key' => $cacheKey, 'ttl' => $ttl]);
                } else {
                    // This might indicate a problem with the cache driver/store
                    $this->logger->warning('UCM Load: Failed to store fresh configuration in cache (put returned false).', ['key' => $cacheKey]);
                }
            } catch (Throwable $e) {
                $this->logger->error('UCM Load: Error writing to cache after fresh load.', [
                    'key' => $cacheKey, 'exception' => $e::class, 'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 🎯 Loads all configuration entries from the database via the DAO.
     * @internal Helper method used by `loadConfig`.
     * @return array<string, array{value: mixed, category?: string|null}> Configuration array (key => ['value' => ..., 'category' => ...]).
     * @sideEffect Logs DB load activity and potential errors.
     */
    private function loadFromDatabase(): array
    {
        $this->logger->debug('UCM DB Load: Requesting all configurations from DAO.');
        $configArray = [];
        try {
            $configs = $this->configDao->getAllConfigs(); // Assumes DAO returns a collection of models/objects
            $loadedCount = 0;
            $ignoredCount = 0;
            foreach ($configs as $config) {
                // Ensure expected properties exist before accessing
                if (property_exists($config, 'key') && property_exists($config, 'value')) {
                    // Ignore entries with null value from DB source? Current logic does this.
                    if ($config->value !== null) {
                        $configArray[$config->key] = [
                            'value' => $config->value,
                            // Handle potential null category or enum object
                            'category' => $config->category instanceof CategoryEnum ? $config->category->value : $config->category,
                        ];
                        $loadedCount++;
                    } else {
                        $this->logger->warning('UCM DB Load: Ignoring config key due to null value from database.', ['key' => $config->key]);
                        $ignoredCount++;
                    }
                } else {
                    $this->logger->warning('UCM DB Load: Skipping record due to missing key/value properties.', ['record_id' => $config->id ?? 'N/A']);
                    $ignoredCount++;
                }
            }
            $this->logger->info('UCM DB Load: Configurations loaded from database.', ['loaded' => $loadedCount, 'ignored' => $ignoredCount]);
        } catch (Throwable $e) {
            // Log DB errors but return an empty array to allow potential ENV override
            $this->logger->error('UCM DB Load: Error loading configurations from database. Returning empty set.', [
                'exception' => $e::class, 'message' => $e->getMessage(),
            ]);
            // Optionally throw PersistenceException if DB load is critical
            // throw new PersistenceException("Failed to load configuration from database.", 0, $e);
        }
        return $configArray;
    }

    /**
     * 🎯 Merges configuration loaded from the database with environment variables.
     * Environment variables do NOT override database values if keys conflict.
     * @internal Helper method used by `loadConfig`.
     * @param array<string, array{value: mixed, category?: string|null}> $dbConfig Config loaded from DB.
     * @return array<string, array{value: mixed, category?: string|null}> Merged configuration array.
     * @sideEffect Logs merge activity.
     */
    private function mergeWithEnvironment(array $dbConfig): array
    {
        $this->logger->debug('UCM Env Merge: Merging environment variables with DB config (ENV does not override).');
        $mergedConfig = $dbConfig; // Start with DB config
        $envCount = 0;
        // Iterate through all environment variables
        foreach ($_ENV as $key => $value) {
            // Add ENV var ONLY if the key doesn't already exist in the DB config
            if (!Arr::has($mergedConfig, $key)) { // Use Arr::has for potential dot notation keys
                $mergedConfig[$key] = ['value' => $value, 'category' => null]; // Assign value, category is null for ENV vars
                $envCount++;
            }
        }
        $this->logger->info('UCM Env Merge: Environment variables merged.', ['added_from_env' => $envCount, 'final_count' => count($mergedConfig)]);
        return $mergedConfig;
    }

    /**
     * 🎯 Checks if a configuration key exists in the currently loaded configuration.
     *
     * @param string $key The configuration key (dot notation supported).
     * @return bool True if the key exists, false otherwise.
     * @sideEffect Logs the check result.
     */
    public function has(string $key): bool
    {
        // Use Arr::has for dot notation support on the internal $config array
        $exists = Arr::has($this->config, $key);
        $this->logger->debug('UCM Check: Configuration key existence check.', ['key' => $key, 'exists' => $exists]);
        return $exists;
    }

    /**
     * 🎯 Retrieves a configuration value by key. Returns default if not found.
     * This method does NOT throw an exception if the key is not found.
     *
     * @param string $key The configuration key (dot notation supported).
     * @param mixed $default The default value to return if the key is not found. Defaults to null.
     * @param bool $silent If true, suppresses the debug log entry for this specific retrieval (useful for frequent checks).
     * @return mixed The configuration value or the default.
     * @sideEffect Logs retrieval attempt/result unless $silent is true.
     */
    public function get(string $key, mixed $default = null, bool $silent = false): mixed
    {
        // Use Arr::get for dot notation support and default value handling
        $configItem = Arr::get($this->config, $key, $default);

        // If Arr::get returned the default, the key wasn't found or had no 'value'
        if ($configItem === $default && !Arr::has($this->config, $key)) {
             if (!$silent) {
                 $this->logger->debug('UCM Get: Configuration key not found. Returning default.', ['key' => $key]);
             }
             return $default;
        }

        // Check if the retrieved item has the expected structure
        if (is_array($configItem) && array_key_exists('value', $configItem)) {
            $value = $configItem['value'];
             if (!$silent) {
                 $logValue = is_scalar($value) || is_null($value) ? $value : '[' . gettype($value) . ']';
                 // Log value type, not the value itself unless scalar/null, to avoid logging sensitive data
                 $this->logger->debug('UCM Get: Retrieved configuration key.', ['key' => $key, 'value_type' => gettype($value)]);
             }
             return $value;
        } else {
             // Key exists but structure is wrong, log warning and return default
             $this->logger->warning('UCM Get: Found key but structure is unexpected. Returning default.', ['key' => $key, 'retrieved' => json_encode($configItem)]);
             return $default;
        }
    }

    /**
     * 🎯 Retrieves a REQUIRED configuration value by key or throws an exception if not found.
     * Use this method when the configuration value is essential for the application's operation.
     *
     * @param string $key The required configuration key (dot notation supported).
     * @param bool $silent If true, suppresses the debug log entry for successful retrieval. Error log for missing key is always generated.
     * @return mixed The configuration value.
     * @throws ConfigNotFoundException If the key does not exist in the configuration.
     * @sideEffect Logs successful retrieval (unless silent) or error on failure.
     */
    public function getOrFail(string $key, bool $silent = false): mixed
    {
        $notFoundMarker = new \stdClass(); // Unique marker object
        // Use the standard get() method with the marker as default
        $value = $this->get($key, $notFoundMarker, $silent);

        // Check if get() returned the marker, indicating the key wasn't found
        if ($value === $notFoundMarker) {
            $errorMessage = "Required configuration key '{$key}' not found in UCM.";
            // Log as ERROR because a required configuration is missing
            $this->logger->error('[UCM GetOrFail] ' . $errorMessage, ['key' => $key]);
            // Throw the specific exception
            throw new ConfigNotFoundException($errorMessage);
        }

        // If we're here, the value was found. get() already logged if needed.
        return $value;
    }


    /**
     * 🎯 Sets or updates a configuration value.
     * Persists the change to the database (triggering versioning and auditing via DAO),
     * updates the in-memory store, and refreshes the cache.
     *
     * @param string $key The configuration key (alphanumeric, _, ., - allowed).
     * @param mixed $value The value to set (must be scalar, array, or null).
     * @param string|null $category Optional category (must match CategoryEnum value if provided).
     * @param int|null $userId Optional ID of the user performing the action for auditing. @privacy-input
     * @param bool $version Create a new version entry? (Passed to DAO).
     * @param bool $audit Create an audit log entry? (Passed to DAO).
     * @return void
     * @throws InvalidArgumentException If key format, value type, or category is invalid.
     * @throws RuntimeException If persistence or cache update fails.
     * @sideEffect Modifies database, internal `$config` array, and cache store. Logs the action.
     */
    public function set(
        string $key,
        mixed $value,
        ?string $category = null,
        ?int $userId = null,
        bool $version = true,
        bool $audit = true,
        string $sourceFile = CategoryEnum::Application->value // Default source file
    ): void {
        $this->logger->info('UCM Set: Attempting to set configuration.', ['key' => $key, 'category' => $category, 'has_value' => $value !== null]);

        // --- Input Validation ---
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $key)) {
            $this->logger->error('UCM Set: Invalid key format.', ['key' => $key]);
            throw new InvalidArgumentException("Configuration key must be alphanumeric with allowed characters: _ . -");
        }
        // Allow arrays now
        if (!is_scalar($value) && !is_null($value) && !is_array($value)) {
            $this->logger->error('UCM Set: Invalid value type.', ['key' => $key, 'type' => gettype($value)]);
            throw new InvalidArgumentException("Configuration value must be scalar, array, or null.");
        }
        $categoryEnum = null;
        $validCategoryString = null;
        if ($category !== null && $category !== '') {
            $categoryEnum = CategoryEnum::tryFrom($category);
            if ($categoryEnum === null) {
                $this->logger->error('UCM Set: Invalid category provided.', ['key' => $key, 'category' => $category]);
                $validCategories = implode(', ', array_column(CategoryEnum::cases(), 'value'));
                throw new InvalidArgumentException("Invalid category '{$category}'. Valid options are: {$validCategories} or null/empty string.");
            }
            $validCategoryString = $categoryEnum->value;
        }
        // --- End Validation ---

        try {
            // Determine old value for audit log before modification
            $oldValue = Arr::get($this->config, $key . '.value', null); // Get current value from memory
            $action = Arr::has($this->config, $key) ? 'updated' : 'created';

            // 1. Persist via DAO (handles DB transaction, versioning, audit)
            $this->configDao->saveConfig(
                key: $key,
                value: $value, // DAO/Model handles encryption
                category: $validCategoryString,
                sourceFile: $sourceFile, // Assuming this is the source file for all UCM entries
                userId: $userId, // Pass userId directly
                createVersion: $version,
                createAudit: $audit,
                oldValueForAudit: $oldValue // Pass old value for comparison in audit if needed
            );

            // 2. Update In-Memory Store
            // Ensure structure consistency [key => ['value'=> ..., 'category' => ...]]
            $this->config[$key] = ['value' => $value, 'category' => $validCategoryString];
            $this->logger->debug('UCM Set: In-memory configuration updated.', ['key' => $key]);

            // 3. Refresh Cache (Incremental)
            $this->refreshConfigCache($key); // Refresh only the modified key

            $this->logger->info('UCM Set: Configuration set successfully.', ['key' => $key, 'action' => $action]);

        } catch (PersistenceException | ConfigNotFoundException $pe) { // Catch specific DAO exceptions
             $this->logger->error('UCM Set: DAO operation failed.', [
                 'key' => $key, 'exception' => $pe::class, 'message' => $pe->getMessage(),
             ]);
             // Re-throw PersistenceException to indicate DB issue
             throw new PersistenceException("Failed to persist configuration key '{$key}'.", 0, $pe);
        } catch (Throwable $e) { // Catch other potential errors (e.g., cache writing)
             $this->logger->error('UCM Set: Failed to set configuration due to unexpected error.', [
                 'key' => $key, 'exception' => $e::class, 'message' => $e->getMessage(),
             ]);
             // Wrap in RuntimeException for consistency
             throw new RuntimeException("Failed to set configuration key '{$key}'.", 0, $e);
        }
    }

    /**
     * 🎯 Deletes a configuration value.
     * Removes the entry from the database (triggering auditing via DAO),
     * updates the in-memory store, and refreshes the cache.
     *
     * @param string $key The configuration key to delete (alphanumeric, _, ., - allowed).
     * @param int|null $userId Optional ID of the user performing the action for auditing. @privacy-input
     * @param bool $audit Create an audit log entry? (Passed to DAO).
     * @return void
     * @throws InvalidArgumentException If key format is invalid.
     * @throws RuntimeException If persistence or cache update fails.
     * @throws PersistenceException If the DAO layer fails during deletion.
     * @sideEffect Modifies database, internal `$config` array, and cache store. Logs the action.
     */
    public function delete(string $key, ?int $userId = null, bool $audit = true): void
    {
        $this->logger->info('UCM Delete: Attempting to delete configuration.', ['key' => $key]);

        // --- Input Validation ---
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $key)) {
            $this->logger->error('UCM Delete: Invalid key format.', ['key' => $key]);
            throw new InvalidArgumentException("Configuration key must be alphanumeric with allowed characters: _ . -");
        }
        // --- End Validation ---

        try {
            $existsInMemory = Arr::has($this->config, $key);
            if (!$existsInMemory) {
                 $this->logger->warning('UCM Delete: Key not found in memory, attempting deletion in DB anyway.', ['key' => $key]);
            }

            // 1. Delete via DAO (handles DB transaction, audit)
            $deleted = $this->configDao->deleteConfigByKey(
                key: $key,
                userId: $userId, // Pass userId directly
                createAudit: $audit
            );

            // 2. Update In-Memory Store (always remove if deletion attempted)
            if ($existsInMemory) {
                Arr::forget($this->config, $key);
                $this->logger->debug('UCM Delete: Configuration removed from in-memory store.', ['key' => $key]);
            }

             // 3. Refresh Cache (Incremental removal)
            $this->refreshConfigCache($key); // Refresh cache for the deleted key

            if ($deleted) {
                $this->logger->info('UCM Delete: Configuration deleted successfully from DB and memory.', ['key' => $key]);
            } else {
                 // DAO reported not found/deleted, but we attempted
                 $this->logger->info('UCM Delete: Key not found in DB for deletion (or already deleted). Memory/Cache updated.', ['key' => $key]);
                 // No exception needed here, the state is consistent (key doesn't exist)
            }

        } catch (PersistenceException $pe) { // Catch specific DAO exception
             $this->logger->error('UCM Delete: DAO operation failed during deletion.', [
                 'key' => $key, 'exception' => $pe::class, 'message' => $pe->getMessage(),
             ]);
             // Re-throw PersistenceException
             throw $pe;
        } catch (Throwable $e) { // Catch other potential errors (e.g., cache update)
            $this->logger->error('UCM Delete: Failed to delete configuration due to unexpected error.', [
                'key' => $key, 'exception' => $e::class, 'message' => $e->getMessage(),
            ]);
             // Wrap in RuntimeException
            throw new RuntimeException("Failed to delete configuration key '{$key}'.", 0, $e);
        }
    }

    /**
     * 🎯 Returns all configuration values (only the values, not categories) from the in-memory store.
     * Note: This might include values loaded from ENV vars that are not in the DB.
     *
     * @return array<string, mixed> Associative array of key => value.
     * @sideEffect Logs the retrieval.
     */
    public function all(): array
    {
        // Use Arr::map to extract only the 'value' from each item in the $config array
        $allValues = Arr::map($this->config, fn($configItem) => $configItem['value'] ?? null);
        $this->logger->debug('UCM Get All: Returning all configuration values from memory.', ['count' => count($allValues)]);
        return $allValues;
    }

    /**
     * 🎯 Refreshes the entire configuration cache or a specific key.
     * Uses locking for full cache refreshes to prevent race conditions.
     *
     * @param string|null $key If null, refreshes the entire cache. If a key is provided, updates/removes that specific key in the cache.
     * @return void
     * @sideEffect Modifies the cache store. Logs cache operations and potential errors/lock issues.
     * @throws RuntimeException If the full cache refresh fails while holding the lock.
     */
    public function refreshConfigCache(?string $key = null): void
    {
        if (!$this->isCacheEnabled()) {
            $this->logger->debug('UCM Cache Refresh: Cache is disabled, skipping refresh.');
            return;
        }
        $cacheKey = self::CACHE_KEY;
        $ttl = $this->getCacheTtl();

        $this->logger->debug('UCM Cache Refresh: Attempting cache refresh.', ['target_key' => $key ?? 'all']);

        // --- Incremental Update/Removal ---
        if ($key !== null) {
            try {
                // Get current full cache content first
                $currentCache = $this->cacheRepository->get($cacheKey, []);
                if (!is_array($currentCache)) $currentCache = []; // Ensure it's an array

                // Check if the key exists in the *current in-memory* config
                if (Arr::has($this->config, $key)) {
                    // Key exists in memory, update/add it in the cache array
                    $currentCache[$key] = Arr::get($this->config, $key); // Get the ['value'=>..., 'category'=>...] structure
                    $logAction = 'Updated/Added';
                } else {
                    // Key does not exist in memory (must have been deleted), remove from cache array
                    unset($currentCache[$key]);
                    $logAction = 'Removed';
                }

                // Put the modified full array back into the cache
                $success = $this->cacheRepository->put($cacheKey, $currentCache, $ttl);
                if ($success) {
                    $this->logger->info('UCM Cache Refresh: Incremental cache refresh successful.', ['key' => $key, 'action' => $logAction]);
                } else {
                    $this->logger->warning('UCM Cache Refresh: Failed to put updated data during incremental refresh (put returned false).', ['key' => $key]);
                }
            } catch (Throwable $e) {
                 $this->logger->error('UCM Cache Refresh: Error during incremental cache refresh.', [
                     'key' => $key, 'exception' => $e::class, 'message' => $e->getMessage(),
                 ]);
                 // Optionally re-throw or handle, but incremental failure might not be critical
            }
            return; // Exit after incremental update
        }

        // --- Full Cache Refresh (with Locking) ---
        $lockKey = $cacheKey . '_lock';
        $lockTimeout = 10; // Seconds to hold the lock

        $this->logger->info('UCM Cache Refresh: Attempting full cache refresh. Acquiring lock.', ['lock_key' => $lockKey, 'timeout' => $lockTimeout]);
        $lock = $this->cacheRepository->lock($lockKey, $lockTimeout);
        try {
            if ($lock->get()) { // Attempt to acquire lock
                $this->logger->info('UCM Cache Refresh: Lock acquired. Performing full refresh.');
                // Reload fresh data from DB/Env into memory first
                $dbConfig = $this->loadFromDatabase();
                $freshConfig = $this->mergeWithEnvironment($dbConfig);
                $this->config = $freshConfig; // Update in-memory state

                // Put the fresh data into the cache
                $success = $this->cacheRepository->put($cacheKey, $this->config, $ttl);
                if ($success) {
                    $this->logger->info('UCM Cache Refresh: Full cache refresh successful. Cache updated.', ['key' => $cacheKey, 'count' => count($this->config), 'ttl' => $ttl]);
                } else {
                    $this->logger->warning('UCM Cache Refresh: Failed to put data during full cache refresh (put returned false).', ['key' => $cacheKey]);
                }
                $lock->release(); // Release lock explicitly after successful operation
                 $this->logger->debug('UCM Cache Refresh: Lock released.', ['lock_key' => $lockKey]);
            } else {
                // Could not acquire lock, another process is likely refreshing
                $this->logger->info('UCM Cache Refresh: Failed to acquire lock for full cache refresh. Another process might be refreshing.', ['lock_key' => $lockKey]);
                // Do not throw an error here, just skip the refresh
            }
        } catch (Throwable $e) {
             $this->logger->error('UCM Cache Refresh: Error occurred during full cache refresh.', [
                'exception' => $e::class, 'message' => $e->getMessage(),
             ]);
             // Ensure lock is released if acquired, even on error
             $lock?->forceRelease();
             $this->logger->debug('UCM Cache Refresh: Lock force released due to error.', ['lock_key' => $lockKey]);
             // Re-throw as RuntimeException because full refresh failed
             throw new RuntimeException("Full cache refresh failed.", 0, $e);
        }
        // Note: Implicit release happens when $lock goes out of scope if not explicitly released, but explicit is clearer.
    }

    /**
     * 🎯 Reloads the configuration from primary sources (DB, Env), optionally invalidating the cache.
     * Bypasses the cache for reading during the reload process itself.
     *
     * @param bool $invalidateCache If true, removes the current configuration from the cache after reloading memory.
     * @return void
     * @sideEffect Overwrites the internal `$this->config` array.
     * @sideEffect Optionally removes the configuration entry from the cache store. Logs the action.
     */
    public function reload(bool $invalidateCache = true): void
    {
        $this->logger->info('UCM Reload: Reloading configuration from primary sources (DB, Env).', ['invalidateCache' => $invalidateCache]);
        // Load directly from DB and merge with ENV, bypassing cache read
        $dbConfig = $this->loadFromDatabase();
        $mergedConfig = $this->mergeWithEnvironment($dbConfig);
        $this->config = $mergedConfig; // Update in-memory config
        $this->logger->info('UCM Reload: In-memory configuration reloaded.', ['count' => count($this->config)]);

        // Optionally invalidate the cache after reloading memory
        if ($invalidateCache && $this->isCacheEnabled()) {
            $cacheKey = self::CACHE_KEY;
            try {
                $success = $this->cacheRepository->forget($cacheKey);
                if ($success) {
                    $this->logger->info('UCM Reload: External cache invalidated.', ['key' => $cacheKey]);
                } else {
                    // Forget returning false might mean the key didn't exist, which is ok.
                    $this->logger->info('UCM Reload: External cache key not found or already invalidated.', ['key' => $cacheKey]);
                }
            } catch (Throwable $e) {
                 // Log error if cache interaction fails
                 $this->logger->error('UCM Reload: Error invalidating external cache.', [
                     'key' => $cacheKey, 'exception' => $e::class, 'message' => $e->getMessage(),
                 ]);
                 // Do not throw, as the main goal (reloading memory) succeeded.
            }
        }
    }

    /**
     * 🎯 Validates if a given string corresponds to a defined constant name in GlobalConstants.
     * This is a helper method, potentially for validating config values before saving.
     *
     * @param string $name The potential constant name to validate.
     * @return void
     * @throws InvalidArgumentException If the name is not a valid defined constant.
     * @sideEffect Logs validation attempt/result.
     * @see \Ultra\UltraConfigManager\Constants\GlobalConstants::validateConstant()
     */
    public function validateConstant(string $name): void
    {
        // Note: Using GlobalConstants statically. Inject if it becomes more complex.
        $this->logger->debug('UCM Validation: Validating constant name.', ['name' => $name]);
        try {
            // Assuming GlobalConstants has a static validation method
            GlobalConstants::validateConstant($name);
            $this->logger->info('UCM Validation: Constant validation successful.', ['name' => $name]);
        } catch (InvalidArgumentException $e) { // Catch the specific exception
            $this->logger->error('UCM Validation: Constant validation failed.', ['name' => $name, 'error' => $e->getMessage()]);
            // Re-throw the original exception
            throw $e;
        }
    }

    // ========================================================================
    // == UI / DTO METHODS ==
    // ========================================================================

    /**
     * 🎯 Retrieves configuration entries formatted for display (e.g., in a UI table).
     * Fetches data via DAO, applies optional filters (TODO), maps to DTOs, and handles pagination.
     *
     * @param array<string, mixed> $filters Optional filters to apply (e.g., ['category' => 'system', 'search' => 'mail']). TODO: Implement filtering logic.
     * @param int|null $perPage Number of items per page for pagination. If null, returns all items as a Collection.
     * @param int $valueMaxLength Maximum length for the displayed value (truncates longer values).
     * @return LengthAwarePaginator<ConfigDisplayData>|IlluminateCollection<int, ConfigDisplayData> Paginated results or a collection of DTOs.
     * @throws PersistenceException If the DAO layer encounters an error retrieving data.
     * @sideEffect Logs retrieval operation and potential errors.
     */
    public function getAllEntriesForDisplay(
        array $filters = [],
        ?int $perPage = 15,
        int $valueMaxLength = 50
    ): LengthAwarePaginator|IlluminateCollection {
        $this->logger->info('UCM Manager: Retrieving entries for UI display.', ['filters' => $filters, 'perPage' => $perPage]);
        try {
            // 1. Fetch all relevant config models from DAO
            $allConfigs = $this->configDao->getAllConfigs(); // Assumes this returns a Collection of Models

            // --- TODO: Implement Filtering Logic ---
            $filteredConfigs = $allConfigs; // Placeholder
            if (!empty($filters)) {
                 $this->logger->debug('UCM Manager: Applying display filters (Filtering TODO).', ['filters' => $filters]);
                 // Example filtering (needs refinement based on actual filter needs)
                 $filteredConfigs = $allConfigs->filter(function ($model) use ($filters) {
                     $passes = true;
                     if (isset($filters['category']) && $model->category?->value !== $filters['category']) {
                         $passes = false;
                     }
                     if (isset($filters['search']) && $filters['search'] !== '') {
                          $search = strtolower($filters['search']);
                          if (!str_contains(strtolower($model->key), $search) && !str_contains(strtolower($model->note ?? ''), $search)) {
                              $passes = false;
                          }
                     }
                     return $passes;
                 });
            }
            // --- End Filtering Placeholder ---

            // 2. Map filtered models to ConfigDisplayData DTOs
            $displayData = $filteredConfigs->map(
                fn ($model) => ConfigDisplayData::fromModel($model, $valueMaxLength)
            )->values(); // Use values() to reset keys for pagination slicing

            // 3. Paginate if requested
            if ($perPage !== null && $perPage > 0) {
                $currentPage = Paginator::resolveCurrentPage('page'); // Get current page from request query string
                // Manually slice the collection for the current page
                $pagedData = $displayData->slice(($currentPage - 1) * $perPage, $perPage);

                // Create the LengthAwarePaginator instance
                $paginator = new LengthAwarePaginator(
                    $pagedData,             // Items for the current page
                    $displayData->count(), // Total number of items (before pagination)
                    $perPage,               // Items per page
                    $currentPage,           // Current page number
                    ['path' => Paginator::resolveCurrentPath()] // Options (base path for links)
                );
                $this->logger->info('UCM Manager: Returning paginated display entries.', ['page' => $currentPage, 'perPage' => $perPage, 'total_filtered' => $displayData->count()]);
                return $paginator;
            } else {
                // Return the full collection if no pagination requested
                $this->logger->info('UCM Manager: Returning all display entries as collection.', ['count' => $displayData->count()]);
                return $displayData;
            }
        } catch (PersistenceException $e) { // Catch specific DAO exception
            $this->logger->error('UCM Manager: DAO failed to get entries for display.', ['error' => $e->getMessage()]);
            throw $e; // Re-throw
        } catch (Throwable $e) { // Catch unexpected errors
            $this->logger->error('UCM Manager: Unexpected error getting entries for display.', ['error' => $e->getMessage(), 'exception' => $e::class]);
            // Wrap in PersistenceException for consistent error type from this layer
            throw new PersistenceException("Unexpected error retrieving configurations for display.", 0, $e);
        }
    }

    /**
     * 🎯 Retrieves detailed data for a specific configuration entry suitable for an edit form.
     * Includes the configuration model itself, its audit history, and version history.
     *
     * @param int $id The ID of the configuration entry.
     * @return ConfigEditData A DTO containing all necessary data for the edit view.
     * @throws ConfigNotFoundException If no configuration entry with the given ID is found.
     * @throws PersistenceException If the DAO layer encounters an error retrieving related data (audits/versions).
     * @sideEffect Logs retrieval operation and potential errors.
     */
    public function findEntryForEdit(int $id): ConfigEditData
    {
        $this->logger->info('UCM Manager: Retrieving entry data for edit form.', ['id' => $id]);
        try {
            // 1. Get the main config entry (don't include trashed)
            $config = $this->configDao->getConfigById($id, false);
            if (!$config) {
                // Log and throw specific exception if not found
                $this->logger->warning('UCM Manager: Config not found for edit.', ['id' => $id]);
                throw new ConfigNotFoundException("Configuration with ID '{$id}' not found for editing.");
            }

            // 2. Get related audit and version history
            $audits = $this->configDao->getAuditsByConfigId($id);
            $versions = $this->configDao->getVersionsByConfigId($id);

            // 3. Create and return the DTO
            $dto = new ConfigEditData($config, $audits, $versions);
            $this->logger->info('UCM Manager: Successfully retrieved data for edit form.', ['id' => $id, 'audit_count' => $audits->count(), 'version_count' => $versions->count()]);
            return $dto;

        } catch (ConfigNotFoundException $e) {
             // Already logged, just re-throw
             throw $e;
        } catch (PersistenceException $e) { // Catch specific DAO errors for related data
             $this->logger->error('UCM Manager: DAO failed to get related history for edit form.', ['id' => $id, 'error' => $e->getMessage()]);
             throw $e; // Re-throw
        } catch (Throwable $e) { // Catch unexpected errors
             $this->logger->error('UCM Manager: Unexpected error getting entry for edit form.', ['id' => $id, 'error' => $e->getMessage(), 'exception' => $e::class]);
             throw new PersistenceException("Unexpected error retrieving configuration data for edit [ID: {$id}].", 0, $e);
        }
    }

    /**
     * 🎯 Retrieves data for a specific configuration entry suitable for viewing its audit trail.
     * Includes the configuration model (even if soft-deleted) and its audit history.
     *
     * @param int $id The ID of the configuration entry.
     * @return ConfigAuditData A DTO containing the config model and its audit records.
     * @throws ConfigNotFoundException If no configuration entry with the given ID is found (including trashed).
     * @throws PersistenceException If the DAO layer encounters an error retrieving the audit history.
     * @sideEffect Logs retrieval operation and potential errors.
     */
    public function findEntryForAudit(int $id): ConfigAuditData
    {
        $this->logger->info('UCM Manager: Retrieving entry data for audit view.', ['id' => $id]);
        try {
            // 1. Get the config entry, including soft-deleted ones
            $config = $this->configDao->getConfigById($id, true); // Pass true for withTrashed
            if (!$config) {
                // Log and throw specific exception if not found (even trashed)
                $this->logger->warning('UCM Manager: Config not found (inc. trashed) for audit view.', ['id' => $id]);
                throw new ConfigNotFoundException("Configuration with ID '{$id}' not found (including trashed) for audit view.");
            }

            // 2. Get related audit history
            $audits = $this->configDao->getAuditsByConfigId($id);

            // 3. Create and return the DTO
            $dto = new ConfigAuditData($config, $audits);
            $this->logger->info('UCM Manager: Successfully retrieved data for audit view.', ['id' => $id, 'audit_count' => $audits->count()]);
            return $dto;

        } catch (ConfigNotFoundException $e) {
             // Already logged, just re-throw
             throw $e;
        } catch (PersistenceException $e) { // Catch specific DAO errors for audits
             $this->logger->error('UCM Manager: DAO failed to get audit history for audit view.', ['id' => $id, 'error' => $e->getMessage()]);
             throw $e; // Re-throw
        } catch (Throwable $e) { // Catch unexpected errors
             $this->logger->error('UCM Manager: Unexpected error getting entry for audit view.', ['id' => $id, 'error' => $e->getMessage(), 'exception' => $e::class]);
             throw new PersistenceException("Unexpected error retrieving configuration data for audit view [ID: {$id}].", 0, $e);
        }
    }

} // End Class UltraConfigManager