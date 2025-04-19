<?php

/**
 * 📜 Oracode DAO Implementation: EloquentConfigDao
 * Manages persistence for UCM configuration using Laravel Eloquent.
 *
 * @package         Ultra\UltraConfigManager\Dao
 * @version         1.2.0 // Oracode v1.5.0 refactor + source_file handling + explicit exceptions.
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 * @since           1.0.0
 */

namespace Ultra\UltraConfigManager\Dao;

// Laravel & PHP Dependencies
use Illuminate\Database\ConnectionInterface; // For potential future DB Manager injection
use Illuminate\Database\Eloquent\Collection as EloquentCollection; // Eloquent's Collection
use Illuminate\Database\Eloquent\ModelNotFoundException; // Specific Eloquent exception for not found
use Illuminate\Database\QueryException; // Specific DB exception for query errors
use Illuminate\Support\Facades\DB; // Used for ::transaction Facade
use Psr\Log\LoggerInterface;
use Throwable;
use Ultra\UltraConfigManager\Constants\GlobalConstants;
// UCM Dependencies
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface; // Implements this interface
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Enums\CategoryEnum; // For potential type hinting/validation if needed
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException;
use Ultra\UltraConfigManager\Exceptions\DuplicateKeyException;
use Ultra\UltraConfigManager\Exceptions\PersistenceException;
use Ultra\UltraConfigManager\Services\VersionManager; // DAO uses VersionManager now

/**
 * 🎯 Purpose: Provides a concrete implementation of the `ConfigDaoInterface` using
 *    Laravel's Eloquent ORM. It is responsible for all direct database interactions
 *    related to configuration entries (`uconfig` table), version history (`uconfig_versions`),
 *    and audit logs (`uconfig_audit`). It encapsulates the database logic, ensuring
 *    data integrity through transactions and mapping database exceptions to domain-specific ones.
 *
 * 🧱 Structure:
 *    - Implements `ConfigDaoInterface`.
 *    - Uses Eloquent models: `UltraConfigModel`, `UltraConfigVersion`, `UltraConfigAudit`.
 *    - Injects `LoggerInterface` (ULM) for logging.
 *    - Injects `VersionManager` to handle version creation logic.
 *    - Uses `DB::transaction` Facade for atomic write operations (`saveConfig`, `deleteConfigByKey`).
 *    - Contains helper methods for audit and version creation (`internalCreateAudit`, `internalCreateVersion`, `calculateNextVersion`).
 *
 * 🧩 Context: Instantiated by `UConfigServiceProvider` and injected into `UltraConfigManager`.
 *    It operates within a Laravel application with a configured database connection and migrated UCM tables.
 *    It's the sole point of direct database contact for UCM core logic.
 *
 * 🛠️ Usage: Primarily used internally by `UltraConfigManager`. Direct usage by application code
 *    is discouraged; interaction should happen via `UltraConfigManager` or the `UConfig` Facade.
 *
 * 💾 State: Reads from and writes to the `uconfig`, `uconfig_versions`, and `uconfig_audit` database tables.
 *
 * 🗝️ Key Methods:
 *    - `getAllConfigs`: Retrieves all active configuration models.
 *    - `getConfigByKey`: Retrieves a single active model by its unique key. Returns null if not found.
 *    - `getConfigById`: Retrieves a single model by ID. Returns null if not found (unless using `findOrFail` logic internally if required).
 *    - `saveConfig`: Atomically creates or updates a configuration entry, triggers versioning via `VersionManager`, and creates an audit log. Handles soft-delete restoration. Throws specific exceptions on failure.
 *    - `deleteConfigByKey`: Atomically soft-deletes a configuration entry and creates an audit log. Returns boolean success.
 *    - `getAuditsByConfigId`: Retrieves audit history for a specific config ID.
 *    - `getVersionsByConfigId`: Retrieves version history for a specific config ID.
 *
 * 🚦 Signals:
 *    - Returns: `EloquentCollection`, `UltraConfigModel|null`, `bool`.
 *    - Throws:
 *        - `ConfigNotFoundException`: If a requested record (by ID or key) is explicitly expected but not found (e.g., if using `findOrFail`). *Note: Current `getConfigBy*` return null on not found.*
 *        - `DuplicateKeyException`: On unique constraint violation for the 'key' column during creation.
 *        - `PersistenceException`: For general database errors (connection, query syntax, transaction failure, etc.).
 *    - Logs: Detailed logs for all operations (retrievals, saves, deletes, errors) via injected `LoggerInterface`.
 *
 * 🛡️ Privacy (GDPR):
 *    - `@privacy-internal`: Directly handles storage and retrieval of configuration data (`value`, `category`, `note`, `source_file`) and associated metadata (`userId` in audit/versions).
 *    - `@privacy-delegated`: Relies on `UltraConfigModel` (and potentially Audit/Version models) for encryption of the `value` field via Eloquent casts (`EncryptedCast`).
 *    - Stores `userId` provided by the caller in audit/version tables. Data minimization should be considered by the caller.
 *    - Stores `source_file` indicating origin ('manual' or filename).
 *
 * 🤝 Dependencies:
 *    - `ConfigDaoInterface`: Interface being implemented.
 *    - Eloquent Models: `UltraConfigModel`, `UltraConfigVersion`, `UltraConfigAudit`.
 *    - `LoggerInterface`: PSR-3 Logger (ULM).
 *    - `VersionManager`: Service for version creation.
 *    - `Illuminate\Support\Facades\DB`: For transactions.
 *    - Exceptions: `ConfigNotFoundException`, `DuplicateKeyException`, `PersistenceException`.
 *    - (Implicit) Laravel Database component and configuration.
 *
 * 🧪 Testing:
 *    - Integration Tests: Use `RefreshDatabase`. Create/update/delete `UltraConfigModel` instances. Verify data in `uconfig`, `uconfig_versions`, `uconfig_audit` tables. Test transaction atomicity (ensure partial changes are rolled back on error). Test exception mapping (e.g., force a unique constraint error, verify `DuplicateKeyException` is thrown). Test `withTrashed` logic.
 *
 * 💡 Logic:
 *    - Read methods (`get*`) use straightforward Eloquent queries (`::all`, `::where`, `::find`). Handle null results gracefully.
 *    - Write/Delete methods (`saveConfig`, `deleteConfigByKey`) wrap Eloquent operations within `DB::transaction` for atomicity.
 *    - `saveConfig` checks for existing records (including trashed) to determine create/update/restore path. It calls `VersionManager` and `internalCreateAudit`.
 *    - `deleteConfigByKey` performs Eloquent soft delete and calls `internalCreateAudit`.
 *    - Exception handling catches specific `QueryException` (checking for duplicate keys) and general `Throwable`, wrapping them in UCM's domain exceptions.
 *    - Internal helpers (`internalCreate*`, `calculateNextVersion`) encapsulate specific sub-tasks within transactions.
 *
 * @package Ultra\UltraConfigManager\Dao
 */
class EloquentConfigDao implements ConfigDaoInterface
{
    /** @var LoggerInterface The injected PSR-3 logger instance. */
    protected readonly LoggerInterface $logger;
    /** @var VersionManager The injected VersionManager service. */
    protected readonly VersionManager $versionManager;

    /**
     * 🎯 Constructor: Injects dependencies required by the DAO.
     *
     * @param LoggerInterface $logger PSR-3 Logger instance (ULM).
     * @param VersionManager $versionManager Service to handle version creation logic.
     */
    public function __construct(LoggerInterface $logger, VersionManager $versionManager)
    {
        $this->logger = $logger;
        $this->versionManager = $versionManager; // Store injected VersionManager
        $this->logger->debug('DAO Lifecycle: EloquentConfigDao initialized.');
    }

    /**
     * {@inheritdoc}
     * Retrieves all non-soft-deleted configuration entries.
     *
     * @return EloquentCollection<int, UltraConfigModel> A collection of UltraConfigModel instances.
     * @throws PersistenceException If a database error occurs during retrieval.
     */
    public function getAllConfigs(): EloquentCollection
    {
        $this->logger->debug('DAO: Attempting to get all active configurations.');
        try {
            // Retrieve only non-deleted records
            $configs = UltraConfigModel::all();
            $this->logger->info('DAO: Retrieved active configurations.', ['count' => $configs->count()]);
            return $configs;
        } catch (QueryException $e) {
            $this->logger->error('DAO: Database error retrieving all configurations.', ['error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
            throw new PersistenceException('Error retrieving all configurations from database.', (int)$e->getCode(), $e);
        } catch (Throwable $e) {
            $this->logger->error('DAO: Unexpected error retrieving all configurations.', ['exception' => $e::class, 'message' => $e->getMessage()]);
            throw new PersistenceException('Unexpected error retrieving all configurations.', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * Retrieves a single non-soft-deleted configuration entry by its unique key.
     *
     * @param string $key The unique configuration key.
     * @return UltraConfigModel|null The found model instance, or null if not found.
     * @throws PersistenceException If a database error occurs during retrieval.
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
            $config = UltraConfigModel::where('key', $key)->first(); // Returns null if not found

            if ($config) {
                $this->logger->info('DAO: Configuration found by key.', ['key' => $key, 'id' => $config->id]);
            } else {
                $this->logger->info('DAO: Configuration not found by key.', ['key' => $key]);
            }
            return $config;
        } catch (QueryException $e) {
            $this->logger->error('DAO: Database error retrieving configuration by key.', ['key' => $key, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
            throw new PersistenceException("Error retrieving configuration with key '{$key}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
            $this->logger->error('DAO: Unexpected error retrieving configuration by key.', ['key' => $key, 'exception' => $e::class, 'message' => $e->getMessage()]);
            throw new PersistenceException("Unexpected error retrieving configuration with key '{$key}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * Retrieves a single configuration entry by its primary ID.
     *
     * @param int $id The primary ID of the configuration entry.
     * @param bool $withTrashed If true, includes soft-deleted records in the search.
     * @return UltraConfigModel|null The found model instance, or null if not found.
     * @throws PersistenceException If a database error occurs during retrieval.
     */
    public function getConfigById(int $id, bool $withTrashed = false): ?UltraConfigModel
    {
        $this->logger->debug('DAO: Attempting to get configuration by ID.', ['id' => $id, 'withTrashed' => $withTrashed]);
        try {
            $query = UltraConfigModel::query();
            if ($withTrashed) {
                $query->withTrashed(); // Include soft-deleted if requested
            }
            // Use find() which returns null if not found
            $config = $query->find($id);

            if ($config) {
                $status = $withTrashed && $config->trashed() ? 'found (trashed)' : 'found (active)';
                $this->logger->info("DAO: Configuration {$status} by ID.", ['id' => $id]);
            } else {
                 $this->logger->info('DAO: Configuration not found by ID.', ['id' => $id, 'withTrashed_checked' => $withTrashed]);
                 // Returning null aligns with the interface/method signature expectation
            }
            return $config;
        } catch (QueryException $e) {
            $this->logger->error('DAO: Database error retrieving configuration by ID.', ['id' => $id, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
            throw new PersistenceException("Error retrieving configuration with ID '{$id}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
            $this->logger->error('DAO: Unexpected error retrieving configuration by ID.', ['id' => $id, 'exception' => $e::class, 'message' => $e->getMessage()]);
            throw new PersistenceException("Unexpected error retrieving configuration with ID '{$id}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * Atomically saves (creates or updates) a configuration entry, creates versions, and audits.
     * Handles restoration of soft-deleted entries if the key is reused.
     *
     * @param string $key Unique configuration key.
     * @param mixed $value Value to save (encryption handled by model).
     * @param string|null $category Optional category string.
     * @param string|null $sourceFile Origin indicator ('manual' or filename). @privacy-internal
     * @param int|null $userId ID of the user performing the action. Null implies system action. @privacy-internal
     * @param bool $createVersion Whether to create a version record.
     * @param bool $createAudit Whether to create an audit record.
     * @param mixed $oldValueForAudit The value *before* the change (plain value). Required if auditing an update.
     * @return UltraConfigModel The saved or updated Eloquent model instance.
     * @throws DuplicateKeyException If the key already exists during creation.
     * @throws PersistenceException For other database errors during the transaction.
     */
    public function saveConfig(
        string $key,
        mixed $value,
        ?string $category,
        ?string $sourceFile, // Added sourceFile
        ?int $userId,         // Made nullable to accept system changes (null user)
        bool $createVersion,
        bool $createAudit,
        mixed $oldValueForAudit // Keep this for explicit old value passing
    ): UltraConfigModel {
        // Use GlobalConstants for clarity when userId is null (system action)
        $effectiveUserId = $userId ?? GlobalConstants::NO_USER;

        $this->logger->debug('DAO: Attempting to save configuration.', [
            'key' => $key,
            'category' => $category,
            'source_file' => $sourceFile,
            'userId' => $effectiveUserId,
            'version' => $createVersion,
            'audit' => $createAudit
        ]);

        // Use DB::transaction for atomicity
        try {
            // We return the result of the transaction closure
            return DB::transaction(function () use (
                $key, $value, $category, $sourceFile, $effectiveUserId, $createVersion, $createAudit, $oldValueForAudit
            ) {
                $action = 'created'; // Default audit action
                $isUpdate = false;

                // Find existing record (including soft-deleted)
                $config = UltraConfigModel::withTrashed()->where('key', $key)->first();

                // Data to be saved/updated
                $dataToSave = [
                    'value' => $value, // Encryption handled by model cast
                    'category' => $category,
                    // 'note' => ..., // Keep existing note if updating, or handle separately
                    'source_file' => $sourceFile, // Save the source file info
                ];

                if ($config) {
                    // --- Config Exists (Update or Restore) ---
                    $isUpdate = true;
                    $configId = $config->id; // Get ID for logging/audit
                    $this->logger->debug('DAO: Found existing config. Preparing update/restore.', ['key' => $key, 'id' => $configId, 'trashed' => $config->trashed()]);

                    if ($config->trashed()) {
                        $action = 'restored';
                        $this->logger->info('DAO: Restoring soft-deleted configuration.', ['key' => $key, 'id' => $configId]);
                        if (!$config->restore()) { // Attempt restore
                             throw new PersistenceException("Failed to restore soft-deleted configuration for key '{$key}'.");
                        }
                    } else {
                        $action = 'updated';
                    }

                    // Preserve existing note if not explicitly provided in $dataToSave
                    // (Assuming $dataToSave doesn't include 'note' unless intended)
                    $dataToSave['note'] = $config->note;

                    // Perform update
                    if (!$config->update($dataToSave)) {
                        throw new PersistenceException("Failed to update configuration for key '{$key}'.");
                    }
                     $this->logger->info('DAO: Configuration ' . ($action === 'restored' ? 'restored and updated.' : 'updated.'), ['key' => $key, 'id' => $configId]);

                } else {
                    // --- Config Does Not Exist (Create) ---
                    $action = 'created';
                    $this->logger->debug('DAO: No existing config found. Creating new.', ['key' => $key]);
                    $dataToSave['key'] = $key; // Add key only on creation
                    $config = UltraConfigModel::create($dataToSave);
                    if (!$config) {
                        // create() returns false only in very rare cases with mass assignment off? Check Eloquent docs.
                        // Usually throws MassAssignmentException or QueryException.
                         throw new PersistenceException("Failed to create new configuration for key '{$key}' using Eloquent create.");
                    }
                    $configId = $config->id; // Get ID of newly created record
                    $this->logger->info('DAO: New configuration created.', ['key' => $key, 'id' => $configId]);
                    // On creation, the effective "old value" for audit is null
                    $oldValueForAudit = null;
                }

                // Refresh model instance to get latest attributes (especially encrypted value if needed)
                $config->refresh();
                $newValueForAudit = $config->value; // Get potentially casted/encrypted value for audit consistency? Or keep plain? Pass plain for now.

                // --- Handle Versioning (if requested AND it was an update/restore) ---
                // Note: Typically, versioning is done on updates, not initial creation. Adjust if needed.
                if ($createVersion) { // Only version updates/restores
                    $this->logger->debug('DAO: Creating version record.', ['config_id' => $configId]);
                    // VersionManager handles the details of version creation
                    $this->versionManager->createVersion($config, $effectiveUserId, $sourceFile); // Pass necessary data
                }

                // --- Handle Auditing (if requested) ---
                if ($createAudit) {
                    $this->logger->debug('DAO: Creating audit record.', ['config_id' => $configId, 'action' => $action]);
                    $this->internalCreateAudit($configId, $action, $oldValueForAudit, $value, $effectiveUserId, $sourceFile); // Pass plain new value
                }

                return $config; // Return the saved/updated model

            }, 3); // Transaction attempts

        } catch (QueryException $e) {
            // Check for unique constraint violation (MySQL: 1062, SQLite: 19/2067, PostgreSQL: 23505)
            $sqlErrorCode = $e->errorInfo[1] ?? null;
            if (in_array($sqlErrorCode, [1062, 23505, 19, 2067])) { // Add specific codes for your DB
                 $this->logger->error('DAO: Duplicate key violation while saving configuration.', ['key' => $key, 'error' => $e->getMessage(), 'sql_code' => $sqlErrorCode]);
                 throw new DuplicateKeyException("Configuration key '{$key}' already exists.", (int)$e->getCode(), $e);
            }
             // General query exception
             $this->logger->error('DAO: Database query error saving configuration.', ['key' => $key, 'error' => $e->getMessage(), 'sql_code' => $sqlErrorCode]);
             throw new PersistenceException("Error saving configuration with key '{$key}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
             // Catch other exceptions like PersistenceException from restore/update/create calls, or unexpected errors
             $this->logger->error('DAO: Unexpected error saving configuration within transaction.', ['key' => $key, 'exception' => $e::class, 'message' => $e->getMessage()]);
             // Re-throw wrapped in PersistenceException if it's not already one of our custom types
             if (!$e instanceof PersistenceException && !$e instanceof DuplicateKeyException && !$e instanceof ConfigNotFoundException) {
                throw new PersistenceException("Unexpected error saving configuration with key '{$key}'.", 0, $e);
             }
             throw $e; // Re-throw original custom exception
        }
    }


    /**
     * {@inheritdoc}
     * Atomically soft-deletes a configuration entry and creates an audit log.
     *
     * @param string $key The unique configuration key to delete.
     * @param int|null $userId ID of the user performing the action. Null implies system action. @privacy-internal
     * @param bool $createAudit Whether to create an audit record for the deletion.
     * @return bool True if the record was found and soft-deleted, false if not found.
     * @throws PersistenceException If a database error occurs during the transaction.
     */
    public function deleteConfigByKey(string $key, ?int $userId, bool $createAudit): bool
    {
        if (empty($key)) {
            $this->logger->warning('DAO: deleteConfigByKey called with empty key.');
            return false;
        }
        $effectiveUserId = $userId ?? GlobalConstants::NO_USER;
        $this->logger->debug('DAO: Attempting to delete configuration by key.', ['key' => $key, 'userId' => $effectiveUserId]);

        try {
            // Use transaction for atomicity (delete + audit)
            return DB::transaction(function () use ($key, $effectiveUserId, $createAudit) {
                // Find the *active* config first (not soft-deleted)
                $config = UltraConfigModel::where('key', $key)->first();

                if (!$config) {
                    $this->logger->warning('DAO: Configuration key not found (or already deleted) for deletion.', ['key' => $key]);
                    return false; // Key not found or already deleted, indicate no action taken
                }

                $configId = $config->id;
                // Store old value before deletion for audit (get plain value)
                $oldValue = $config->getOriginal('value'); // Get value before potential casts for audit

                // --- Perform Soft Delete ---
                $deleted = $config->delete(); // Eloquent soft delete

                if (!$deleted) {
                    // This usually indicates an issue with Eloquent events or model state
                    $this->logger->error('DAO: Eloquent failed to soft-delete configuration (delete() returned false).', ['key' => $key, 'id' => $configId]);
                    throw new PersistenceException("Eloquent failed to soft-delete configuration with key '{$key}'.");
                }

                $this->logger->info('DAO: Configuration soft-deleted.', ['key' => $key, 'id' => $configId]);

                // --- Handle Auditing (if requested) ---
                if ($createAudit) {
                    $this->logger->debug('DAO: Creating audit record for deletion.', ['config_id' => $configId]);
                    // Pass the OLD value and NULL for the new value
                    $this->internalCreateAudit($configId, 'deleted', $oldValue, null, $effectiveUserId, $config->source_file); // Pass source file from deleted record
                }

                return true; // Deletion successful

            }, 3); // Transaction attempts

        } catch (QueryException $e) {
             $this->logger->error('DAO: Database error during configuration deletion transaction.', ['key' => $key, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
             throw new PersistenceException("Error deleting configuration with key '{$key}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
             // Catch exceptions potentially thrown by internalCreateAudit or other unexpected issues
             $this->logger->error('DAO: Unexpected error during configuration deletion transaction.', ['key' => $key, 'exception' => $e::class, 'message' => $e->getMessage()]);
             if (!$e instanceof PersistenceException) { // Avoid double wrapping
                throw new PersistenceException("Unexpected error deleting configuration with key '{$key}'.", 0, $e);
             }
             throw $e; // Re-throw original PersistenceException
        }
    }


    /**
     * {@inheritdoc}
     * Retrieves audit history for a specific configuration entry.
     *
     * @param int $configId The ID of the configuration entry.
     * @return EloquentCollection<int, UltraConfigAudit> Collection of audit records, ordered descending by creation date.
     * @throws PersistenceException If a database error occurs.
     */
    public function getAuditsByConfigId(int $configId): EloquentCollection
    {
        $this->logger->debug('DAO: Attempting to get audits by config ID.', ['config_id' => $configId]);
        try {
            $audits = UltraConfigAudit::where('uconfig_id', $configId)
                                     ->orderBy('created_at', 'desc') // Show most recent first
                                     ->get();
            $this->logger->info('DAO: Retrieved audits by config ID.', ['config_id' => $configId, 'count' => $audits->count()]);
            return $audits;
        } catch (QueryException $e) {
             $this->logger->error('DAO: Database error retrieving audits.', ['config_id' => $configId, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
             throw new PersistenceException("Error retrieving audits for config ID '{$configId}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
             $this->logger->error('DAO: Unexpected error retrieving audits.', ['config_id' => $configId, 'exception' => $e::class, 'message' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error retrieving audits for config ID '{$configId}'.", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     * Retrieves version history for a specific configuration entry.
     *
     * @param int $configId The ID of the configuration entry.
     * @param string $orderBy Column to order by (default: 'version').
     * @param string $direction Order direction ('asc' or 'desc', default: 'desc').
     * @return EloquentCollection<int, UltraConfigVersion> Collection of version records.
     * @throws PersistenceException If a database error occurs.
     */
    public function getVersionsByConfigId(int $configId, string $orderBy = 'version', string $direction = 'desc'): EloquentCollection
    {
         $this->logger->debug('DAO: Attempting to get versions by config ID.', ['config_id' => $configId, 'orderBy' => $orderBy, 'direction' => $direction]);
         // Basic validation for order parameters
         $validOrderBy = ['version', 'created_at'];
         $validDirection = ['asc', 'desc'];
         $orderBy = in_array(strtolower($orderBy), $validOrderBy, true) ? strtolower($orderBy) : 'version';
         $direction = in_array(strtolower($direction), $validDirection, true) ? strtolower($direction) : 'desc';

        try {
            $versions = UltraConfigVersion::where('uconfig_id', $configId)
                                          ->orderBy($orderBy, $direction)
                                          ->get();
            $this->logger->info('DAO: Retrieved versions by config ID.', ['config_id' => $configId, 'count' => $versions->count()]);
            return $versions;
        } catch (QueryException $e) {
             $this->logger->error('DAO: Database error retrieving versions.', ['config_id' => $configId, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
             throw new PersistenceException("Error retrieving versions for config ID '{$configId}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
             $this->logger->error('DAO: Unexpected error retrieving versions.', ['config_id' => $configId, 'exception' => $e::class, 'message' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error retrieving versions for config ID '{$configId}'.", 0, $e);
        }
    }

    // --- Schema Check Method (Required by Interface) ---

    /**
     * {@inheritdoc}
     * Indicates that this DAO implementation relies on the database schema.
     */
    public function shouldBypassSchemaChecks(): bool
    {
        // This Eloquent implementation *requires* the database schema.
        return false;
    }

    // --- Internal Helper Methods ---

    /**
     * 🛡️ Creates an audit log record. Assumes running within a transaction.
     * Encrypts values before storing if the Audit model uses EncryptedCast.
     * @internal Should only be called from within transactional methods like saveConfig/deleteConfigByKey.
     *
     * @param int $configId The ID of the related configuration entry.
     * @param string $action The action performed ('created', 'updated', 'deleted', 'restored').
     * @param mixed|null $oldValue The value *before* the change (plain value). Null for creation/restoration from null.
     * @param mixed|null $newValue The value *after* the change (plain value). Null for deletion.
     * @param int $userId ID of the user performing the action (use GlobalConstants::NO_USER for system). @privacy-internal
     * @param string|null $sourceFile The source file or 'manual' indicator. @privacy-internal
     * @return UltraConfigAudit The created audit model instance.
     * @throws PersistenceException If the audit record cannot be created.
     */
    protected function internalCreateAudit(int $configId, string $action, mixed $oldValue, mixed $newValue, int $userId, ?string $sourceFile): UltraConfigAudit
    {
        $this->logger->debug('DAO Internal: Creating audit record.', ['config_id' => $configId, 'action' => $action, 'userId' => $userId, 'source_file' => $sourceFile]);
        try {
            // Pass plain values, let the model's cast handle encryption
            $audit = UltraConfigAudit::create([
                'uconfig_id' => $configId,
                'action' => $action,
                'old_value' => $oldValue, // Pass raw value
                'new_value' => $newValue, // Pass raw value
                'user_id' => $userId,
                'source_file' => $sourceFile, // Save source file
            ]);
             $this->logger->info('DAO Internal: Audit record created successfully.', ['audit_id' => $audit->id]);
            return $audit;
        } catch (QueryException $e) {
             $this->logger->error('DAO Internal: Database error creating audit record.', ['config_id' => $configId, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
             throw new PersistenceException("Error creating audit record for config ID '{$configId}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
             $this->logger->error('DAO Internal: Unexpected error creating audit record.', ['config_id' => $configId, 'exception' => $e::class, 'message' => $e->getMessage()]);
             throw new PersistenceException("Unexpected error creating audit record for config ID '{$configId}'.", 0, $e);
        }
    }

     /**
      * 🛡️ Calculates the next sequential version number for a configuration.
      * @internal Helper method.
      * @param int $configId
      * @return int The next version number (starts at 1).
      * @throws PersistenceException If DB query fails.
      */
    protected function calculateNextVersion(int $configId): int
    {
         $this->logger->debug('DAO Internal: Calculating next version number.', ['config_id' => $configId]);
        try {
             // Find the maximum existing version for this config ID
            $latestVersion = UltraConfigVersion::where('uconfig_id', $configId)->max('version');
             // Next version is max + 1, or 1 if no versions exist yet (max returns null)
            $nextVersion = ($latestVersion ?? 0) + 1;
             $this->logger->debug('DAO Internal: Next version number calculated.', ['config_id' => $configId, 'next_version' => $nextVersion]);
            return $nextVersion;
        } catch (QueryException $e) {
             $this->logger->error('DAO Internal: Database error calculating next version number.', ['config_id' => $configId, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
             throw new PersistenceException("Error calculating next version for config ID '{$configId}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
            $this->logger->error('DAO Internal: Unexpected error calculating next version number.', ['config_id' => $configId, 'exception' => $e::class, 'message' => $e->getMessage()]);
            throw new PersistenceException("Unexpected error calculating next version for config ID '{$configId}'.", 0, $e);
        }
    }

     // Note: internalCreateVersion is now handled by the injected VersionManager service
     // protected function internalCreateVersion(...) { ... } // Removed from DAO

} // End Class EloquentConfigDao