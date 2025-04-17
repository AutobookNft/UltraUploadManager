<?php

/**
 * 📜 Oracode Class: UltraConfigManager
 *
 * @package         Ultra\UltraConfigManager
 * @version         1.2.0 // Incremented version for UI/DTO methods addition
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator; // Needed for manual pagination
use Illuminate\Support\Collection as IlluminateCollection;
use Psr\Log\LoggerInterface;
use Throwable;
use Ultra\ErrorManager\Facades\UltraError; // Keep for potential future use? No, remove if not used.
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigAuditData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigDisplayData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigEditData;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException;
use Ultra\UltraConfigManager\Exceptions\PersistenceException;
use Ultra\UltraConfigManager\Services\VersionManager;



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
 *    - Provides public API methods (`get`, `set`, `delete`, `has`, `all`, `reload`, `validateConstant`, `getAllEntriesForDisplay`, `findEntryForEdit`, `findEntryForAudit`).
 *    - Internal methods handle loading (`loadConfig`, `loadFromDatabase`, `mergeWithEnvironment`) and cache management (`isCacheEnabled`, `getCacheTtl`, `refreshConfigCache`).
 *
 * 🧩 Context: Instantiated typically as a singleton by `UConfigServiceProvider`. Used throughout
 *    the application (directly or via the `UConfig` Facade) wherever configuration access is needed.
 *    Operates within a Laravel application context, relying on injected Cache and Logger.
 *
 * 🛠️ Usage:
 *    `UConfig::get('key', 'default')`
 *    `UConfig::set('key', 'value', 'category', Auth::id())`
 *    `UConfig::delete('key', Auth::id())`
 *    `UConfig::all()`
 *    `UConfig::reload()`
 *    `$manager->getAllEntriesForDisplay()`
 *    `$manager->findEntryForEdit($id)`
 *    `$manager->findEntryForAudit($id)`
 *
 * 💾 State:
 *    - `$config`: In-memory cache of key => {value, category}.
 *    - `$cacheRepository`: External cache state.
 *    - Database State: Managed via `configDao`.
 *
 * 🗝️ Key Methods:
 *    - `get`, `set`, `delete`: Core CRUD operations.
 *    - `has`, `all`: Read operations on in-memory state.
 *    - `loadConfig`, `reload`: State hydration management.
 *    - `refreshConfigCache`: External cache synchronization.
 *    - `validateConstant`: Constant validation helper.
 *    - `getAllEntriesForDisplay`, `findEntryForEdit`, `findEntryForAudit`: Data retrieval for UI/DTOs.
 *
 * 🚦 Signals:
 *    - Returns values, DTOs, Collections, Paginators.
 *    - Throws `InvalidArgumentException`, `ConfigNotFoundException`, `DuplicateKeyException`, `PersistenceException`.
 *    - Logs actions and errors via `LoggerInterface`.
 *    - Interacts with cache store (`CACHE_KEY`).
 *    - Triggers audit/version record creation via DAO.
 *
 * 🛡️ Privacy (GDPR):
 *    - Manages potentially sensitive configuration data (encrypted at rest via Model).
 *    - Handles `userId` for auditing (passed to DAO).
 *    - `@privacy-input`: Accepts optional `$userId` in `set`/`delete`.
 *    - `@privacy-internal`: Passes `$userId` to `configDao`. Relies on DAO/Model for secure storage.
 *    - `@privacy-delegated`: Encryption handled by Model. `userId` sensitivity context depends on host app.
 *    - Recommendation: Avoid storing direct PII in configuration values.
 *
 * 🤝 Dependencies:
 *    - `ConfigDaoInterface`, `VersionManager`, `GlobalConstants`, `CacheRepository`, `LoggerInterface`, `CategoryEnum`, DTOs, Custom Exceptions.
 *    - (Implicit) Laravel Application Context.
 *
 * 🧪 Testing:
 *    - Unit Test: Mock all dependencies. Test logic of public methods. Verify mock calls.
 *    - Integration Test: Use `RefreshDatabase`. Test against real DB connection and cache driver. Verify state changes.
 *
 * 💡 Logic:
 *    - Loading: Cache -> DB -> Env (merge).
 *    - Mutation (`set`/`delete`): DB (via DAO including transaction/version/audit) -> Memory -> Cache.
 *    - Error Handling: Catch specific and generic exceptions, log, re-throw custom exceptions.
 *    - Caching: Single key, TTL, locking for refresh, incremental updates.
 *    - Validation: Key format, value type, category enum, constants.
 *
 * @package     Ultra\UltraConfigManager
 */
class UltraConfigManager
{
    /**
     * @var array<string, array{value: mixed, category?: string|null}>
     */
    private array $config = [];
    protected readonly ConfigDaoInterface $configDao;
    protected readonly VersionManager $versionManager;
    protected readonly GlobalConstants $globalConstants;
    protected readonly CacheRepository $cacheRepository;
    protected readonly LoggerInterface $logger;
    protected readonly int $cacheTtl;
    protected readonly bool $cacheEnabled;
    private const CACHE_KEY = 'ultra_config.cache';

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
            if (function_exists('app') && (!app()->bound('db') || !app()->bound('cache'))) {
                 $this->logger->warning('UCM Lifecycle: DB or Cache services not bound. Skipping initial config load.', [
                    'db_bound' => function_exists('app') && app()->bound('db'),
                    'cache_bound' => function_exists('app') && app()->bound('cache'),
                 ]);
            } else {
                $this->loadConfig();
            }
        } else {
             $this->logger->info('UCM Lifecycle: Initial config load skipped via constructor flag.');
        }
    }

    private function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    private function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function loadConfig(): void
    {
        $this->logger->info('UCM Load: Starting configuration load sequence.');
        $useCache = $this->isCacheEnabled();
        $cacheKey = self::CACHE_KEY;

        if ($useCache) {
            $this->logger->debug('UCM Load: Cache is enabled. Attempting to load from cache.', ['key' => $cacheKey]);
            try {
                $cachedConfig = $this->cacheRepository->get($cacheKey);
                if (is_array($cachedConfig)) {
                    $this->config = $cachedConfig;
                    $this->logger->info('UCM Load: Configuration successfully loaded from cache.', ['key' => $cacheKey, 'count' => count($cachedConfig)]);
                    return;
                } else {
                     $this->logger->info('UCM Load: Cache miss or invalid data type in cache.', ['key' => $cacheKey, 'type' => gettype($cachedConfig)]);
                }
            } catch (Throwable $e) {
                $this->logger->error('UCM Load: Error reading from cache. Proceeding with DB load.', [
                    'key' => $cacheKey, 'exception' => $e::class, 'message' => $e->getMessage(),
                ]);
            }
        } else {
            $this->logger->info('UCM Load: Cache is disabled. Loading directly from primary sources.');
        }

        $this->logger->info('UCM Load: Loading configuration from database and environment.');
        $dbConfig = $this->loadFromDatabase();
        $mergedConfig = $this->mergeWithEnvironment($dbConfig);
        $this->config = $mergedConfig;
        $this->logger->info('UCM Load: Configuration loaded from DB/Env.', ['db_count' => count($dbConfig), 'merged_count' => count($mergedConfig)]);

        if ($useCache) {
            $ttl = $this->getCacheTtl();
            try {
                $success = $this->cacheRepository->put($cacheKey, $this->config, $ttl);
                if ($success) {
                    $this->logger->info('UCM Load: Fresh configuration stored in cache.', ['key' => $cacheKey, 'ttl' => $ttl]);
                } else {
                    $this->logger->warning('UCM Load: Failed to store fresh configuration in cache.', ['key' => $cacheKey]);
                }
            } catch (Throwable $e) {
                $this->logger->error('UCM Load: Error writing to cache after fresh load.', [
                    'key' => $cacheKey, 'exception' => $e::class, 'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function loadFromDatabase(): array
    {
        $this->logger->debug('UCM DB Load: Requesting all configurations from DAO.');
        $configArray = [];
        try {
            $configs = $this->configDao->getAllConfigs();
            $loadedCount = 0;
            $ignoredCount = 0;
            foreach ($configs as $config) {
                if ($config->value !== null) {
                    $configArray[$config->key] = [
                        'value' => $config->value,
                        'category' => $config->category?->value,
                    ];
                    $loadedCount++;
                } else {
                    $this->logger->warning('UCM DB Load: Ignoring config key due to null value.', ['key' => $config->key]);
                    $ignoredCount++;
                }
            }
            $this->logger->info('UCM DB Load: Configurations loaded from database.', ['loaded' => $loadedCount, 'ignored_null' => $ignoredCount]);
        } catch (Throwable $e) {
            $this->logger->error('UCM DB Load: Error loading configurations from database. Returning empty set.', [
                'exception' => $e::class, 'message' => $e->getMessage(),
            ]);
        }
        return $configArray;
    }

    private function mergeWithEnvironment(array $dbConfig): array
    {
        $this->logger->debug('UCM Env Merge: Merging environment variables with DB config.');
        $mergedConfig = $dbConfig;
        $envCount = 0;
        foreach ($_ENV as $key => $value) {
            if (!array_key_exists($key, $mergedConfig)) {
                $mergedConfig[$key] = ['value' => $value, 'category' => null];
                $envCount++;
            }
        }
        $this->logger->info('UCM Env Merge: Environment variables merged.', ['added_count' => $envCount]);
        return $mergedConfig;
    }

    public function has(string $key): bool
    {
        $exists = array_key_exists($key, $this->config);
        $this->logger->debug('UCM Check: Configuration key check.', ['key' => $key, 'exists' => $exists]);
        return $exists;
    }

    public function get(string $key, mixed $default = null, bool $silent = false): mixed
    {
        if (array_key_exists($key, $this->config)) {
            $value = $this->config[$key]['value'];
            if (!$silent) {
                $logValue = is_scalar($value) || is_null($value) ? $value : '[' . gettype($value) . ']';
                $this->logger->debug('UCM Get: Retrieved configuration key.', ['key' => $key, 'value_type' => gettype($value)]);
            }
            return $value;
        } else {
            if (!$silent) {
                $this->logger->debug('UCM Get: Configuration key not found. Returning default.', ['key' => $key]);
            }
            return $default;
        }
    }

    public function set(
        string $key,
        mixed $value,
        ?string $category = null,
        ?int $userId = null,
        bool $version = true,
        bool $audit = true
    ): void {
        $this->logger->info('UCM Set: Attempting to set configuration.', ['key' => $key, 'category' => $category, 'has_value' => $value !== null]);

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $key)) {
            $this->logger->error('UCM Set: Invalid key format.', ['key' => $key]);
            throw new \InvalidArgumentException("Configuration key must be alphanumeric with allowed characters: _ . -");
        }
        if (!is_scalar($value) && !is_null($value) && !is_array($value)) {
            $this->logger->error('UCM Set: Invalid value type.', ['key' => $key, 'type' => gettype($value)]);
            throw new \InvalidArgumentException("Configuration value must be scalar, array, or null.");
        }
        $categoryEnum = CategoryEnum::tryFrom($category ?? '');
        if ($category !== null && $category !== '' && $categoryEnum === null) {
             $this->logger->error('UCM Set: Invalid category.', ['key' => $key, 'category' => $category]);
             $validCategories = implode(', ', array_column(CategoryEnum::cases(), 'value'));
             throw new \InvalidArgumentException("Invalid category '{$category}'. Valid options are: {$validCategories} or null/empty string.");
        }
        $validCategoryString = $categoryEnum?->value;

        try {
            $oldValue = $this->config[$key]['value'] ?? null;
            $action = array_key_exists($key, $this->config) ? 'updated' : 'created';

            $this->configDao->saveConfig(
                key: $key,
                value: $value,
                category: $validCategoryString,
                userId: $userId ?? $this->globalConstants::NO_USER,
                createVersion: $version,
                createAudit: $audit,
                oldValueForAudit: $oldValue
            );

             $this->config[$key] = ['value' => $value, 'category' => $validCategoryString];
             $this->logger->debug('UCM Set: In-memory configuration updated.', ['key' => $key]);

             $this->refreshConfigCache($key);

             $this->logger->info('UCM Set: Configuration set successfully.', ['key' => $key, 'action' => $action]);

        } catch (Throwable $e) {
             $this->logger->error('UCM Set: Failed to set configuration due to persistence or caching error.', [
                 'key' => $key, 'exception' => $e::class, 'message' => $e->getMessage(),
             ]);
             throw new \RuntimeException("Failed to set configuration key '{$key}'.", 0, $e);
        }
    }

    public function delete(string $key, ?int $userId = null, bool $audit = true): void
    {
        $this->logger->info('UCM Delete: Attempting to delete configuration.', ['key' => $key]);

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $key)) {
            $this->logger->error('UCM Delete: Invalid key format.', ['key' => $key]);
            throw new \InvalidArgumentException("Configuration key must be alphanumeric with allowed characters: _ . -");
        }

        try {
            $existsInMemory = array_key_exists($key, $this->config);
            if (!$existsInMemory) {
                 $this->logger->warning('UCM Delete: Key not found in memory, attempting deletion in DB anyway.', ['key' => $key]);
            }

            $deleted = $this->configDao->deleteConfigByKey(
                key: $key,
                userId: $userId ?? $this->globalConstants::NO_USER,
                createAudit: $audit
            );

            if ($deleted) {
                unset($this->config[$key]);
                $this->logger->debug('UCM Delete: Configuration removed from in-memory store.', ['key' => $key]);
                $this->refreshConfigCache($key);
                $this->logger->info('UCM Delete: Configuration deleted successfully.', ['key' => $key]);
            } else {
                 if ($existsInMemory) {
                     $this->logger->error('UCM Delete: DAO failed to delete key, but it existed in memory. State might be inconsistent.', ['key' => $key]);
                 } else {
                     $this->logger->info('UCM Delete: Key not found in DB for deletion.', ['key' => $key]);
                 }
                 if ($existsInMemory) unset($this->config[$key]);
                 $this->refreshConfigCache($key);
            }

        } catch (Throwable $e) {
            $this->logger->error('UCM Delete: Failed to delete configuration due to persistence or caching error.', [
                'key' => $key, 'exception' => $e::class, 'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Failed to delete configuration key '{$key}'.", 0, $e);
        }
    }

    public function all(): array
    {
        $allValues = array_map(fn($configItem) => $configItem['value'], $this->config);
        $this->logger->debug('UCM Get All: Returning all configuration values from memory.', ['count' => count($allValues)]);
        return $allValues;
    }

    public function refreshConfigCache(?string $key = null): void
    {
        if (!$this->isCacheEnabled()) {
            $this->logger->debug('UCM Cache Refresh: Cache is disabled, skipping refresh.');
            return;
        }
        $cacheKey = self::CACHE_KEY;
        $ttl = $this->getCacheTtl();
        $lockKey = $cacheKey . '_lock';
        $lockTimeout = 10;

        $this->logger->debug('UCM Cache Refresh: Attempting cache refresh.', ['key' => $key ?? 'all']);

        if ($key !== null) {
            try {
                $currentCache = $this->cacheRepository->get($cacheKey, []);
                if (!is_array($currentCache)) $currentCache = [];

                if (array_key_exists($key, $this->config)) {
                    $currentCache[$key] = $this->config[$key];
                    $logAction = 'Updated/Added';
                } else {
                    unset($currentCache[$key]);
                    $logAction = 'Removed';
                }
                $success = $this->cacheRepository->put($cacheKey, $currentCache, $ttl);
                if ($success) {
                    $this->logger->info('UCM Cache Refresh: Incremental cache refresh successful.', ['key' => $key, 'action' => $logAction]);
                } else {
                    $this->logger->warning('UCM Cache Refresh: Failed to put updated data during incremental refresh.', ['key' => $key]);
                }
            } catch (Throwable $e) {
                 $this->logger->error('UCM Cache Refresh: Error during incremental cache refresh.', [
                     'key' => $key, 'exception' => $e::class, 'message' => $e->getMessage(),
                 ]);
            }
            return;
        }

        $this->logger->info('UCM Cache Refresh: Attempting full cache refresh. Acquiring lock.', ['lock_key' => $lockKey]);
        $lock = $this->cacheRepository->lock($lockKey, $lockTimeout);
        try {
            if ($lock->get()) {
                $this->logger->info('UCM Cache Refresh: Lock acquired. Performing full refresh.');
                $dbConfig = $this->loadFromDatabase();
                $freshConfig = $this->mergeWithEnvironment($dbConfig);
                $this->config = $freshConfig;
                $success = $this->cacheRepository->put($cacheKey, $this->config, $ttl);
                if ($success) {
                    $this->logger->info('UCM Cache Refresh: Full cache refresh successful. Cache updated.', ['key' => $cacheKey, 'count' => count($this->config), 'ttl' => $ttl]);
                } else {
                    $this->logger->warning('UCM Cache Refresh: Failed to put data during full cache refresh.', ['key' => $cacheKey]);
                }
            } else {
                $this->logger->warning('UCM Cache Refresh: Failed to acquire lock for full cache refresh. Skipping.', ['lock_key' => $lockKey]);
            }
        } catch (Throwable $e) {
            $this->logger->error('UCM Cache Refresh: Error during full cache refresh.', [
                'exception' => $e::class, 'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Full cache refresh failed.", 0, $e);
        } finally {
            $lock?->release();
        }
    }

    public function reload(bool $invalidateCache = true): void
    {
        $this->logger->info('UCM Reload: Reloading configuration from primary sources, bypassing cache.', ['invalidateCache' => $invalidateCache]);
        $dbConfig = $this->loadFromDatabase();
        $mergedConfig = $this->mergeWithEnvironment($dbConfig);
        $this->config = $mergedConfig;
        $this->logger->info('UCM Reload: In-memory configuration reloaded.', ['count' => count($this->config)]);

        if ($invalidateCache && $this->isCacheEnabled()) {
            $cacheKey = self::CACHE_KEY;
            try {
                $success = $this->cacheRepository->forget($cacheKey);
                if ($success) {
                    $this->logger->info('UCM Reload: External cache invalidated.', ['key' => $cacheKey]);
                } else {
                    $this->logger->warning('UCM Reload: Failed to invalidate external cache.', ['key' => $cacheKey]);
                }
            } catch (Throwable $e) {
                 $this->logger->error('UCM Reload: Error invalidating external cache.', [
                     'key' => $cacheKey, 'exception' => $e::class, 'message' => $e->getMessage(),
                 ]);
            }
        }
    }

    public function validateConstant(string $name): void
    {
        $this->logger->debug('UCM Validation: Validating constant.', ['name' => $name]);
        try {
            $this->globalConstants::validateConstant($name);
            $this->logger->info('UCM Validation: Constant validation successful.', ['name' => $name]);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('UCM Validation: Constant validation failed.', ['name' => $name, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ========================================================================
    // == NUOVI METODI PER UI / DTO ==
    // ========================================================================

    /**
     * @param array<string, mixed> $filters
     * @param ?int $perPage
     * @param int $valueMaxLength
     * @return LengthAwarePaginator|IlluminateCollection<int, ConfigDisplayData>
     * @throws PersistenceException
     */
    public function getAllEntriesForDisplay(
        array $filters = [],
        ?int $perPage = 15,
        int $valueMaxLength = 50
    ): LengthAwarePaginator|IlluminateCollection {
        $this->logger->info('UCM Manager: Retrieving entries for display.', ['filters' => $filters, 'perPage' => $perPage]);
        try {
            $allConfigs = $this->configDao->getAllConfigs();
            // TODO: Apply filters on collection
            $displayData = $allConfigs->map(
                fn ($model) => ConfigDisplayData::fromModel($model, $valueMaxLength)
            )->values();

            if ($perPage !== null && $perPage > 0) {
                $currentPage = Paginator::resolveCurrentPage('page');
                $pagedData = $displayData->slice(($currentPage - 1) * $perPage, $perPage);
                $paginator = new LengthAwarePaginator(
                    $pagedData, $displayData->count(), $perPage, $currentPage,
                    ['path' => Paginator::resolveCurrentPath()]
                );
                $this->logger->info('UCM Manager: Returning paginated display entries.', ['page' => $currentPage, 'perPage' => $perPage, 'total' => $displayData->count()]);
                return $paginator;
            } else {
                $this->logger->info('UCM Manager: Returning all display entries as collection.', ['count' => $displayData->count()]);
                return $displayData;
            }
        } catch (PersistenceException $e) {
            $this->logger->error('UCM Manager: Failed to get entries for display.', ['error' => $e->getMessage()]);
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('UCM Manager: Unexpected error getting entries for display.', ['error' => $e->getMessage()]);
            throw new PersistenceException("Unexpected error retrieving configurations for display.", 0, $e);
        }
    }

    /**
     * @param int $id
     * @return ConfigEditData
     * @throws ConfigNotFoundException
     * @throws PersistenceException
     */
    public function findEntryForEdit(int $id): ConfigEditData
    {
        $this->logger->info('UCM Manager: Retrieving entry data for edit.', ['id' => $id]);
        try {
            $config = $this->configDao->getConfigById($id, false);
            if (!$config) throw new ConfigNotFoundException("Configuration with ID '{$id}' not found for editing.");
            $audits = $this->configDao->getAuditsByConfigId($id);
            $versions = $this->configDao->getVersionsByConfigId($id);
            $dto = new ConfigEditData($config, $audits, $versions);
            $this->logger->info('UCM Manager: Successfully retrieved data for edit.', ['id' => $id, 'audit_count' => $audits->count(), 'version_count' => $versions->count()]);
            return $dto;
        } catch (ConfigNotFoundException $e) {
             $this->logger->warning('UCM Manager: Config not found for edit.', ['id' => $id]);
             throw $e;
        } catch (PersistenceException $e) {
             $this->logger->error('UCM Manager: Failed to get related history for edit.', ['id' => $id, 'error' => $e->getMessage()]);
             throw $e;
        } catch (Throwable $e) {
             $this->logger->error('UCM Manager: Unexpected error getting entry for edit.', ['id' => $id, 'error' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error retrieving configuration data for edit [ID: {$id}].", 0, $e);
        }
    }

    /**
     * @param int $id
     * @return ConfigAuditData
     * @throws ConfigNotFoundException
     * @throws PersistenceException
     */
    public function findEntryForAudit(int $id): ConfigAuditData
    {
        $this->logger->info('UCM Manager: Retrieving entry data for audit view.', ['id' => $id]);
        try {
            $config = $this->configDao->getConfigById($id, true);
            if (!$config) throw new ConfigNotFoundException("Configuration with ID '{$id}' not found (including trashed) for audit view.");
            $audits = $this->configDao->getAuditsByConfigId($id);
            $dto = new ConfigAuditData($config, $audits);
            $this->logger->info('UCM Manager: Successfully retrieved data for audit view.', ['id' => $id, 'audit_count' => $audits->count()]);
            return $dto;
        } catch (ConfigNotFoundException $e) {
             $this->logger->warning('UCM Manager: Config not found for audit view.', ['id' => $id]);
             throw $e;
        } catch (PersistenceException $e) {
             $this->logger->error('UCM Manager: Failed to get audit history for audit view.', ['id' => $id, 'error' => $e->getMessage()]);
             throw $e;
        } catch (Throwable $e) {
             $this->logger->error('UCM Manager: Unexpected error getting entry for audit view.', ['id' => $id, 'error' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error retrieving configuration data for audit view [ID: {$id}].", 0, $e);
        }
    }

} // End Class UltraConfigManager