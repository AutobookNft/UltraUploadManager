<?php

/**
 * 📜 Oracode Interface: ConfigDaoInterface
 *
 * @package         Ultra\UltraConfigManager\Dao
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Dao;

use Illuminate\Support\Collection;
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException; // Da creare
use Ultra\UltraConfigManager\Exceptions\DuplicateKeyException;   // Da creare
use Ultra\UltraConfigManager\Exceptions\PersistenceException;  // Da creare
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;

/**
 * 🎯 Purpose: Defines the contract for Data Access Objects (DAO) responsible for persisting,
 *    retrieving, and deleting configuration data within the UltraConfigManager ecosystem.
 *    It abstracts the underlying storage mechanism (e.g., Eloquent, file, API) and ensures
 *    atomicity for operations involving configuration, versioning, and auditing.
 *
 * 🧱 Structure: Declares methods for:
 *    - Retrieving configurations (all, by ID, by key).
 *    - Saving (creating or updating) configuration atomically with version/audit.
 *    - Deleting configuration atomically with audit.
 *    - Retrieving audit/version history.
 *    - Signaling test-specific behavior (schema bypass).
 *
 * 🧩 Context: Implemented by concrete DAO classes (e.g., `EloquentConfigDao`). Used primarily
 *    by `UltraConfigManager` to interact with the persistence layer.
 *
 * 🛠️ Usage: Injected into `UltraConfigManager` via Dependency Injection. The Manager calls
 *    methods on this interface without knowing the specific implementation details.
 *
 * 💾 State: Implementations manage the connection and interaction with the chosen data store.
 *
 * 🗝️ Key Methods:
 *    - `getAllConfigs`: Retrieve all active configurations.
 *    - `getConfigByKey`: Retrieve a single configuration by its unique key.
 *    - `saveConfig`: Atomically create or update a config entry, including version and audit.
 *    - `deleteConfigByKey`: Atomically soft-delete a config entry and create an audit record.
 *    - `getAuditsByConfigId`: Retrieve audit history for a config.
 *    - `getVersionsByConfigId`: Retrieve version history for a config (Nuovo).
 *
 * 🚦 Signals:
 *    - Methods may return specific Models (`UltraConfigModel`, `UltraConfigAudit`, `UltraConfigVersion`) or Collections.
 *    - Methods may return `null` or `false` to indicate "not found" or failure (prefer exceptions for errors).
 *    - Methods SHOULD throw specific exceptions (`ConfigNotFoundException`, `DuplicateKeyException`, `PersistenceException`) on errors.
 *
 * 🛡️ Privacy (GDPR): Implementations are responsible for handling data securely.
 *    - `saveConfig`/`deleteConfigByKey` receive `$userId` which might be PII; implementations must handle it appropriately (e.g., storing it, not logging it excessively).
 *    - Encryption of sensitive data (like config values) should be handled by the Model layer (`EncryptedCast`) invoked during persistence, not directly in the DAO interface/implementation.
 *    - `@privacy-input`: Methods `saveConfig`, `deleteConfigByKey` accept `$userId`.
 *    - `@privacy-internal`: Implementations store/manage config data, versions, audits (potentially including `$userId`).
 *    - `@privacy-output`: Methods returning Models might contain sensitive data (expected to be encrypted if necessary by Model casts).
 *
 * 🤝 Dependencies: Implementations depend on specific Models (`UltraConfigModel`, etc.) and potentially database connections or other storage APIs.
 *
 * 🧪 Testing: Implementations should be thoroughly tested. Mock implementations (Fake DAOs) based on this interface are crucial for testing `UltraConfigManager` in isolation.
 *
 * 💡 Logic: Key principle is **atomicity** for `saveConfig` and `deleteConfigByKey`. These methods encapsulate multiple related database operations (config + version + audit) that must succeed or fail together. Error handling should favour specific, informative exceptions over returning `null`/`false` for actual errors.
 *
 * @package Ultra\UltraConfigManager\Dao
 */
interface ConfigDaoInterface
{
    /**
     * ⛓️ Retrieve all active (non-soft-deleted) configuration entries.
     *
     * @return Collection<int, UltraConfigModel> A collection of UltraConfigModel instances. Returns empty collection if none found.
     * @throws PersistenceException If there is an error communicating with the data store.
     * @readOperation
     */
    public function getAllConfigs(): Collection;

    /**
     * 🕵️‍♀️ Retrieve a single active (non-soft-deleted) configuration by its unique key.
     *
     * @param string $key The unique configuration key.
     * @return UltraConfigModel|null The found configuration model, or null if no active config matches the key.
     * @throws PersistenceException If there is an error communicating with the data store.
     * @readOperation
     */
    public function getConfigByKey(string $key): ?UltraConfigModel;

    /**
     * 🕵️‍♀️ Retrieve a single configuration by its ID, including soft-deleted ones if specified.
     * (Potentially useful for audit/version history access even after deletion).
     *
     * @param int $id The configuration ID.
     * @param bool $withTrashed Include soft-deleted records? (Defaults to false)
     * @return UltraConfigModel|null The found configuration model, or null if not found.
     * @throws PersistenceException If there is an error communicating with the data store.
     * @readOperation
     */
    public function getConfigById(int $id, bool $withTrashed = false): ?UltraConfigModel;

    /**
     * 💾 Atomically creates a new configuration entry or updates an existing one.
     * Handles creation of version and audit records as part of the same transaction if requested.
     * Restores soft-deleted entries if an existing key is reused.
     *
     * @param string $key The unique configuration key.
     * @param mixed $value The value to store (will be encrypted by the Model).
     * @param string|null $category The category string (should match `CategoryEnum` value).
     * @param int $userId The ID of the user performing the action.
     * @param bool $createVersion If true, create a `UltraConfigVersion` record.
     * @param bool $createAudit If true, create a `UltraConfigAudit` record.
     * @param mixed $oldValueForAudit The previous value (needed only if `$createAudit` is true and it's an update).
     *
     * @return UltraConfigModel The created or updated configuration model.
     * @throws DuplicateKeyException If attempting to create a key that already actively exists (though this method handles updates). Implementations might refine this.
     * @throws PersistenceException If any part of the transaction (config save, version save, audit save) fails.
     * @writeOperation
     * @transactional Requires atomic execution.
     */
    public function saveConfig(
        string $key,
        mixed $value,
        ?string $category,
        ?string $sourceFile, // Optional source file for the config
        int $userId, // Changed from ?int to int, use GlobalConstants::NO_USER if null
        bool $createVersion,
        bool $createAudit,
        mixed $oldValueForAudit // Explicitly pass old value for audit accuracy
    ): UltraConfigModel;

    /**
     * 🔥 Atomically soft-deletes a configuration entry by its key.
     * Creates an audit record for the deletion as part of the same transaction if requested.
     *
     * @param string $key The unique configuration key to delete.
     * @param int $userId The ID of the user performing the deletion.
     * @param bool $createAudit If true, create an `UltraConfigAudit` record for the deletion.
     *
     * @return bool True if the configuration was found and successfully soft-deleted, false otherwise (e.g., key not found).
     * @throws PersistenceException If the deletion or audit logging fails.
     * @writeOperation
     * @transactional Requires atomic execution.
     */
    public function deleteConfigByKey(
        string $key,
        int $userId, // Changed from ?int to int
        bool $createAudit
    ): bool;

    /**
     * 🧾 Retrieve all audit entries for a given configuration ID, ordered chronologically.
     *
     * @param int $configId The ID of the configuration.
     * @return Collection<int, UltraConfigAudit> A collection of audit records. Returns empty collection if none found.
     * @throws PersistenceException If there is an error communicating with the data store.
     * @readOperation
     */
    public function getAuditsByConfigId(int $configId): Collection;

    /**
     * 🧬 Retrieve all version entries for a given configuration ID, ordered by version number (desc or asc).
     *
     * @param int $configId The ID of the configuration.
     * @param string $orderBy 'version' or 'created_at'.
     * @param string $direction 'asc' or 'desc'.
     * @return Collection<int, UltraConfigVersion> A collection of version records. Returns empty collection if none found.
     * @throws PersistenceException If there is an error communicating with the data store.
     * @readOperation
     */
    public function getVersionsByConfigId(int $configId, string $orderBy = 'version', string $direction = 'desc'): Collection;

    // --- Metodi di supporto/deprecati? ---

    // createConfig, updateConfig, deleteConfig(UltraConfigModel):
    // Questi potrebbero essere deprecati o resi protected nell'implementazione
    // se `saveConfig` e `deleteConfigByKey` coprono tutti i casi d'uso necessari
    // per il Manager. Manteniamoli commentati per ora per discussione.
    /*
    public function createConfig(array $data): UltraConfigModel;
    public function updateConfig(UltraConfigModel $config, array $data): UltraConfigModel;
    public function deleteConfig(UltraConfigModel $config, ?int $userId = null): void; // Firma vecchia
    public function createVersion(UltraConfigModel $config, int $version): UltraConfigVersion; // Potrebbe diventare interna al DAO
    public function getLatestVersion(int $configId): int; // Potrebbe diventare interna al DAO
    public function createAudit(int $configId, string $action, ?string $oldValue, ?string $newValue, ?int $userId): UltraConfigAudit; // Potrebbe diventare interna al DAO
    */

    /**
     * 🔐 Signals if the DAO implementation should bypass runtime schema checks.
     * Useful for Fake DAOs in test environments that don't interact with a real database schema.
     *
     * @return bool True if schema checks (like `Schema::hasTable`) should be skipped by the caller.
     * @testSupportive
     */
    public function shouldBypassSchemaChecks(): bool;
}