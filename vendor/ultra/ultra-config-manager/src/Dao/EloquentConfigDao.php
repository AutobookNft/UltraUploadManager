<?php

/**
 * 📜 Oracode Class: EloquentConfigDao
 *
 * @package         Ultra\UltraConfigManager\Dao
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Dao;

use Illuminate\Database\ConnectionInterface; // Per type hint connessione DB
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB; // Usato SOLO per ::transaction (potrebbe essere iniettato DatabaseManager)
use Psr\Log\LoggerInterface;
use Throwable; // Per catturare eccezioni generiche
use Ultra\UltraConfigManager\Constants\GlobalConstants; // Per NO_USER
use Ultra\UltraConfigManager\Enums\CategoryEnum; // Per type hint potenziale
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException;
use Ultra\UltraConfigManager\Exceptions\DuplicateKeyException;
use Ultra\UltraConfigManager\Exceptions\PersistenceException;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;

/**
 * 🎯 Purpose: Implements the ConfigDaoInterface using Laravel's Eloquent ORM.
 *    Handles all persistence logic for configuration entries, versions, and audits,
 *    interacting directly with the database models (`UltraConfigModel`, `UltraConfigVersion`, `UltraConfigAudit`).
 *    Ensures atomicity for write operations using database transactions.
 *
 * 🧱 Structure: Implements all methods defined in `ConfigDaoInterface`. Relies on Eloquent models
 *    for database interactions and schema definition. Uses database transactions for `saveConfig`
 *    and `deleteConfigByKey`.
 *
 * 🧩 Context: Instantiated by the `UConfigServiceProvider` and injected into `UltraConfigManager`.
 *    Requires a configured database connection within the Laravel application.
 *
 * 🛠️ Usage: Used internally by `UltraConfigManager`. Not typically called directly from application code.
 *
 * 💾 State: Interacts directly with the database state via Eloquent models.
 *
 * 🗝️ Key Methods:
 *    - `getAllConfigs`: Retrieves all active configs.
 *    - `getConfigByKey`: Retrieves a single active config by key.
 *    - `getConfigById`: Retrieves a single config by ID (optionally including trashed).
 *    - `saveConfig`: Atomically creates/updates config + version + audit.
 *    - `deleteConfigByKey`: Atomically soft-deletes config + creates audit.
 *    - `getAuditsByConfigId`: Retrieves audit history.
 *    - `getVersionsByConfigId`: Retrieves version history.
 *
 * 🚦 Signals:
 *    - Returns Eloquent Models or Collections on success.
 *    - Returns `null` for `get*` methods when a record is not found.
 *    - Returns `bool` for `deleteConfigByKey` indicating success/failure (not found).
 *    - Throws `ConfigNotFoundException` (subclass of PersistenceException) if findOrFail logic is used implicitly or explicitly and fails.
 *    - Throws `DuplicateKeyException` (subclass of PersistenceException) on unique constraint violation during creation.
 *    - Throws `PersistenceException` for general database errors during read or write operations.
 *
 * 🛡️ Privacy (GDPR):
 *    - Handles storage of configuration data, which could be sensitive. Encryption is managed by `UltraConfigModel`'s `EncryptedCast`.
 *    - Stores `userId` in `uconfig_audit` and potentially `uconfig_versions` tables if the schema includes it. The DAO ensures it's stored but relies on the caller (Manager) for the correct ID.
 *    - `@privacy-internal`: Persists config values (encrypted), versions, audits, including `userId`.
 *    - `@privacy-delegated`: Relies on Model casts for encryption at rest. Relies on caller for providing correct `userId`.
 *
 * 🤝 Dependencies:
 *    - `Eloquent Models`: `UltraConfigModel`, `UltraConfigVersion`, `UltraConfigAudit`.
 *    - `LoggerInterface`: For logging operations and errors.
 *    - `Illuminate\Database\ConnectionInterface` (Optional but recommended): For managing transactions explicitly instead of `DB::transaction` Facade. For now, we keep `DB::transaction` for simplicity as it's widely used, although it's technically a Facade call.
 *    - (Implicit) Laravel Database Configuration.
 *
 * 🧪 Testing:
 *    - Integration Test: Use `RefreshDatabase`. Test each DAO method against a real database connection (e.g., sqlite :memory:). Verify data creation, retrieval, updates, soft deletes, audit/version creation, and exception throwing.
 *    - Fake DAO: Create a mock implementation of `ConfigDaoInterface` for unit testing `UltraConfigManager`.
 *
 * 💡 Logic:
 *    - Uses Eloquent methods (`::all`, `::where`, `::findOrFail`, `::create`, `->update`, `->delete`) for CRUD operations.
 *    - Leverages `DB::transaction` to ensure atomicity of compound write operations (`saveConfig`, `deleteConfigByKey`).
 *    - Catches specific database exceptions (`QueryException`) and general `Throwable` to wrap them in custom, semantic exceptions (`DuplicateKeyException`, `PersistenceException`).
 *    - `saveConfig` handles create vs. update logic, including restoring soft-deleted records if the key is reused.
 *    - `deleteConfigByKey` performs a soft delete and triggers audit creation within its transaction.
 *
 * @package Ultra\UltraConfigManager\Dao
 */
class EloquentConfigDao implements ConfigDaoInterface
{
    protected readonly LoggerInterface $logger;
    // Optional: Inject DatabaseManager for more control over transactions/connections
    // protected readonly DatabaseManager $db;

    /**
     * 🎯 Constructor: Injects dependencies.
     *
     * @param LoggerInterface $logger PSR-3 Logger instance.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logger->debug('EloquentConfigDao initialized.');
    }

    /**
     * {@inheritdoc}
     * @throws PersistenceException
     */
    public function getAllConfigs(): Collection
    {
        $this->logger->debug('DAO: Attempting to get all configurations.');
        try {
            $configs = UltraConfigModel::all();
            $this->logger->info('DAO: Retrieved all configurations.', ['count' => $configs->count()]);
            return $configs;
        } catch (QueryException $e) {
            $this->logger->error('DAO: Database error retrieving all configurations.', ['error' => $e->getMessage()]);
            throw new PersistenceException('Error retrieving all configurations from database.', $e->getCode(), $e);
        } catch (Throwable $e) {
            $this->logger->error('DAO: Unexpected error retrieving all configurations.', ['error' => $e->getMessage()]);
            throw new PersistenceException('Unexpected error retrieving all configurations.', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws PersistenceException
     */
    public function getConfigByKey(string $key): ?UltraConfigModel
    {
        if (empty($key)) {
            $this->logger->warning('DAO: getConfigByKey called with empty key.');
            return null;
        }
        $this->logger->debug('DAO: Attempting to get configuration by key.', ['key' => $key]);
        try {
            // Find active (non-soft-deleted) config
            $config = UltraConfigModel::where('key', $key)->first();

            if ($config) {
                $this->logger->info('DAO: Configuration found by key.', ['key' => $key, 'id' => $config->id]);
            } else {
                $this->logger->info('DAO: Configuration not found by key.', ['key' => $key]);
            }
            return $config;
        } catch (QueryException $e) {
            $this->logger->error('DAO: Database error retrieving configuration by key.', ['key' => $key, 'error' => $e->getMessage()]);
            throw new PersistenceException("Error retrieving configuration with key '{$key}'.", $e->getCode(), $e);
        } catch (Throwable $e) {
            $this->logger->error('DAO: Unexpected error retrieving configuration by key.', ['key' => $key, 'error' => $e->getMessage()]);
            throw new PersistenceException("Unexpected error retrieving configuration with key '{$key}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws PersistenceException
     * @throws ConfigNotFoundException
     */
    public function getConfigById(int $id, bool $withTrashed = false): ?UltraConfigModel
    {
        $this->logger->debug('DAO: Attempting to get configuration by ID.', ['id' => $id, 'withTrashed' => $withTrashed]);
        try {
            $query = UltraConfigModel::query();
            if ($withTrashed) {
                $query->withTrashed();
            }
            // Use find() which returns null if not found, instead of findOrFail()
            $config = $query->find($id);

            if ($config) {
                $this->logger->info('DAO: Configuration found by ID.', ['id' => $id]);
            } else {
                 $this->logger->info('DAO: Configuration not found by ID.', ['id' => $id]);
                 // Throw specific exception if not found, aligning with findOrFail behavior if needed by caller
                 // Alternatively, just return null as per the method signature. Let's return null for now.
                 // throw new ConfigNotFoundException("Configuration with ID '{$id}' not found.");
            }
            return $config;
        } catch (QueryException $e) {
            $this->logger->error('DAO: Database error retrieving configuration by ID.', ['id' => $id, 'error' => $e->getMessage()]);
            throw new PersistenceException("Error retrieving configuration with ID '{$id}'.", $e->getCode(), $e);
        } catch (Throwable $e) {
            $this->logger->error('DAO: Unexpected error retrieving configuration by ID.', ['id' => $id, 'error' => $e->getMessage()]);
            throw new PersistenceException("Unexpected error retrieving configuration with ID '{$id}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws DuplicateKeyException
     * @throws PersistenceException
     */
    public function saveConfig(
        string $key,
        mixed $value,
        ?string $category,
        int $userId,
        bool $createVersion,
        bool $createAudit,
        mixed $oldValueForAudit // Required if createAudit is true and it's an update
    ): UltraConfigModel {
        $this->logger->debug('DAO: Attempting to save configuration.', ['key' => $key, 'category' => $category, 'userId' => $userId]);

        // Use DB::transaction for atomicity
        try {
            return DB::transaction(function () use ($key, $value, $category, $userId, $createVersion, $createAudit, $oldValueForAudit) {
                $action = 'created'; // Default action for audit

                // Check if config exists (including soft-deleted) to decide between create/update/restore
                $config = UltraConfigModel::withTrashed()->where('key', $key)->first();

                $data = ['value' => $value, 'category' => $category];

                if ($config) {
                    // --- Config Exists ---
                    $action = 'updated'; // It's an update or restore-update
                    $this->logger->debug('DAO: Found existing config (or trashed). Updating.', ['key' => $key, 'id' => $config->id, 'trashed' => $config->trashed()]);

                    if ($config->trashed()) {
                        $this->logger->info('DAO: Restoring soft-deleted configuration.', ['key' => $key, 'id' => $config->id]);
                        $config->restore();
                        $action = 'restored'; // More specific audit action
                    }

                    // Perform the update
                    $config->update($data);
                    $this->logger->info('DAO: Configuration updated.', ['key' => $key, 'id' => $config->id]);

                } else {
                    // --- Config Does Not Exist ---
                    $this->logger->debug('DAO: No existing config found. Creating new.', ['key' => $key]);
                    $data['key'] = $key; // Add key only on creation
                    $config = UltraConfigModel::create($data);
                    $this->logger->info('DAO: New configuration created.', ['key' => $key, 'id' => $config->id]);
                    // On creation, oldValueForAudit is implicitly null
                    $oldValueForAudit = null;
                }

                // --- Handle Versioning (if requested) ---
                if ($createVersion) {
                    $this->logger->debug('DAO: Creating version record.', ['config_id' => $config->id]);
                    $nextVersionNumber = $this->calculateNextVersion($config->id);
                    $this->internalCreateVersion($config, $nextVersionNumber, $userId); // Pass userId if schema supports it
                }

                // --- Handle Auditing (if requested) ---
                if ($createAudit) {
                    $this->logger->debug('DAO: Creating audit record.', ['config_id' => $config->id, 'action' => $action]);
                    $this->internalCreateAudit($config->id, $action, $oldValueForAudit, $config->value, $userId);
                }

                return $config; // Return the saved/updated model

            }, 3); // Optional: Number of attempts for transaction

        } catch (QueryException $e) {
             // Check for unique constraint violation (specific codes depend on DB)
             // Example for MySQL: error code 1062
            if ($e->errorInfo[1] == 1062) {
                 $this->logger->error('DAO: Duplicate key violation while saving configuration.', ['key' => $key, 'error' => $e->getMessage()]);
                 throw new DuplicateKeyException("Configuration key '{$key}' already exists.", $e->getCode(), $e);
            }
             $this->logger->error('DAO: Database error saving configuration.', ['key' => $key, 'error' => $e->getMessage()]);
             throw new PersistenceException("Error saving configuration with key '{$key}'.", $e->getCode(), $e);
        } catch (Throwable $e) {
             $this->logger->error('DAO: Unexpected error saving configuration.', ['key' => $key, 'error' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error saving configuration with key '{$key}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws PersistenceException
     */
    public function deleteConfigByKey(string $key, int $userId, bool $createAudit): bool
    {
        if (empty($key)) {
            $this->logger->warning('DAO: deleteConfigByKey called with empty key.');
            return false;
        }
        $this->logger->debug('DAO: Attempting to delete configuration by key.', ['key' => $key, 'userId' => $userId]);

        try {
            return DB::transaction(function () use ($key, $userId, $createAudit) {
                // Find the active config first
                $config = UltraConfigModel::where('key', $key)->first();

                if (!$config) {
                    $this->logger->warning('DAO: Configuration key not found for deletion.', ['key' => $key]);
                    return false; // Key not found, deletion considered "failed" in the sense of not performed
                }

                // Store old value before deletion for audit
                $oldValue = $config->value;

                // Perform soft delete
                $deleted = $config->delete(); // Returns true/false

                if (!$deleted) {
                    $this->logger->error('DAO: Eloquent failed to soft-delete configuration.', ['key' => $key, 'id' => $config->id]);
                    // Throw exception as the delete operation itself failed
                    throw new PersistenceException("Failed to soft-delete configuration with key '{$key}'.");
                }

                $this->logger->info('DAO: Configuration soft-deleted.', ['key' => $key, 'id' => $config->id]);

                // --- Handle Auditing (if requested) ---
                if ($createAudit) {
                    $this->logger->debug('DAO: Creating audit record for deletion.', ['config_id' => $config->id]);
                    $this->internalCreateAudit($config->id, 'deleted', $oldValue, null, $userId);
                }

                return true; // Deletion successful

            }, 3); // Transaction attempts

        } catch (QueryException $e) {
             $this->logger->error('DAO: Database error deleting configuration.', ['key' => $key, 'error' => $e->getMessage()]);
             throw new PersistenceException("Error deleting configuration with key '{$key}'.", $e->getCode(), $e);
        } catch (Throwable $e) {
             // Catch exceptions potentially thrown by internalCreateAudit as well
             $this->logger->error('DAO: Unexpected error deleting configuration.', ['key' => $key, 'error' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error deleting configuration with key '{$key}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws PersistenceException
     */
    public function getAuditsByConfigId(int $configId): Collection
    {
        $this->logger->debug('DAO: Attempting to get audits by config ID.', ['config_id' => $configId]);
        try {
            // Use Eloquent Model for consistency and features (casts, relations)
            $audits = UltraConfigAudit::where('uconfig_id', $configId)
                                     ->orderBy('created_at', 'desc') // Order chronologically
                                     ->get();
            $this->logger->info('DAO: Retrieved audits by config ID.', ['config_id' => $configId, 'count' => $audits->count()]);
            return $audits; // Returns empty collection if none found
        } catch (QueryException $e) {
             $this->logger->error('DAO: Database error retrieving audits.', ['config_id' => $configId, 'error' => $e->getMessage()]);
             throw new PersistenceException("Error retrieving audits for config ID '{$configId}'.", $e->getCode(), $e);
        } catch (Throwable $e) {
             $this->logger->error('DAO: Unexpected error retrieving audits.', ['config_id' => $configId, 'error' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error retrieving audits for config ID '{$configId}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws PersistenceException
     */
    public function getVersionsByConfigId(int $configId, string $orderBy = 'version', string $direction = 'desc'): Collection
    {
         $this->logger->debug('DAO: Attempting to get versions by config ID.', ['config_id' => $configId, 'orderBy' => $orderBy, 'direction' => $direction]);
         // Validate parameters
         $validOrderBy = ['version', 'created_at'];
         $validDirection = ['asc', 'desc'];
         if (!in_array(strtolower($orderBy), $validOrderBy)) $orderBy = 'version';
         if (!in_array(strtolower($direction), $validDirection)) $direction = 'desc';

        try {
            $versions = UltraConfigVersion::where('uconfig_id', $configId)
                                          ->orderBy($orderBy, $direction)
                                          ->get();
            $this->logger->info('DAO: Retrieved versions by config ID.', ['config_id' => $configId, 'count' => $versions->count()]);
            return $versions; // Returns empty collection if none found
        } catch (QueryException $e) {
             $this->logger->error('DAO: Database error retrieving versions.', ['config_id' => $configId, 'error' => $e->getMessage()]);
             throw new PersistenceException("Error retrieving versions for config ID '{$configId}'.", $e->getCode(), $e);
        } catch (Throwable $e) {
             $this->logger->error('DAO: Unexpected error retrieving versions.', ['config_id' => $configId, 'error' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error retrieving versions for config ID '{$configId}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBypassSchemaChecks(): bool
    {
        // This is the Eloquent implementation, it relies on the schema.
        return false;
    }

    // --- Internal Helper Methods (kept protected/private) ---

    /**
     * 🛡️ Calculates the next sequential version number for a configuration.
     * @internal Used within saveConfig transaction.
     * @param int $configId
     * @return int The next version number (starts at 1).
     * @throws PersistenceException If DB query fails.
     */
    protected function calculateNextVersion(int $configId): int
    {
         $this->logger->debug('DAO Internal: Calculating next version.', ['config_id' => $configId]);
        try {
             // Find the maximum existing version for this config ID
            $latestVersion = UltraConfigVersion::where('uconfig_id', $configId)->max('version');
            $nextVersion = ($latestVersion ?? 0) + 1;
             $this->logger->debug('DAO Internal: Next version calculated.', ['config_id' => $configId, 'nextVersion' => $nextVersion]);
            return $nextVersion;
        } catch (QueryException $e) {
             $this->logger->error('DAO Internal: Database error calculating next version.', ['config_id' => $configId, 'error' => $e->getMessage()]);
             throw new PersistenceException("Error calculating next version for config ID '{$configId}'.", $e->getCode(), $e);
        }
    }

    /**
     * 🛡️ Creates a version record. MUST be called within a transaction.
     * @internal Used within saveConfig transaction.
     * @param UltraConfigModel $config The parent configuration model.
     * @param int $versionNumber The version number to assign.
     * @param int $userId The ID of the user performing the action. // MODIFICATO: userId ora non è nullable qui
     * @return UltraConfigVersion
     * @throws PersistenceException
     */
    // Modifichiamo la firma per rendere userId obbligatorio qui, useremo GlobalConstants::NO_USER se necessario prima di chiamare
    protected function internalCreateVersion(UltraConfigModel $config, int $versionNumber, int $userId): UltraConfigVersion
    {
        $this->logger->debug('DAO Internal: Creating version record.', ['config_id' => $config->id, 'version' => $versionNumber, 'userId' => $userId]); // Log userId
        try {
            $versionData = [
                'uconfig_id' => $config->id,
                'version' => $versionNumber,
                'key' => $config->key,
                'category' => $config->category?->value,
                'note' => $config->note,
                'value' => $config->value, // Assume model accessor gives correct value to store
                'user_id' => $userId, // <-- Aggiunto user_id
            ];

            $version = UltraConfigVersion::create($versionData);
            $this->logger->info('DAO Internal: Version record created.', ['version_id' => $version->id]);
            return $version;
        } catch (QueryException $e) {
            // ... gestione eccezioni ...
            $this->logger->error('DAO Internal: Database error creating version record.', ['config_id' => $config->id, 'error' => $e->getMessage()]);
            throw new PersistenceException("Error creating version record for config ID '{$config->id}'.", $e->getCode(), $e);
        } catch (Throwable $e) {
            // ... gestione eccezioni ...
            $this->logger->error('DAO Internal: Unexpected error creating version record.', ['config_id' => $config->id, 'error' => $e->getMessage()]);
            throw new PersistenceException("Unexpected error creating version record for config ID '{$config->id}'.", 0, $e);
        }
    }

    /**
     * 🛡️ Creates an audit record. MUST be called within a transaction.
     * @internal Used within saveConfig/deleteConfigByKey transactions.
     * @param int $configId
     * @param string $action ('created', 'updated', 'deleted', 'restored')
     * @param mixed $oldValue The value *before* the change (plain text, will be encrypted by Audit model).
     * @param mixed $newValue The value *after* the change (plain text, will be encrypted by Audit model).
     * @param int $userId
     * @return UltraConfigAudit
     * @throws PersistenceException
     */
    protected function internalCreateAudit(int $configId, string $action, mixed $oldValue, mixed $newValue, int $userId): UltraConfigAudit
    {
        $this->logger->debug('DAO Internal: Creating audit record.', ['config_id' => $configId, 'action' => $action, 'userId' => $userId]);
        try {
            $audit = UltraConfigAudit::create([
                'uconfig_id' => $configId,
                'action' => $action,
                 // Pass raw values; encryption handled by UltraConfigAudit model's cast
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'user_id' => $userId,
            ]);
             $this->logger->info('DAO Internal: Audit record created.', ['audit_id' => $audit->id]);
            return $audit;
        } catch (QueryException $e) {
             $this->logger->error('DAO Internal: Database error creating audit record.', ['config_id' => $configId, 'error' => $e->getMessage()]);
             throw new PersistenceException("Error creating audit record for config ID '{$configId}'.", $e->getCode(), $e);
        } catch (Throwable $e) {
             $this->logger->error('DAO Internal: Unexpected error creating audit record.', ['config_id' => $configId, 'error' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error creating audit record for config ID '{$configId}'.", 0, $e);
        }
    }
}