<?php

/**
 * 📜 Oracode Service: VersionManager
 * Handles business logic related to configuration versioning.
 *
 * @package         Ultra\UltraConfigManager\Services
 * @version         1.1.0 // Added createVersion method, Oracode v1.5.0 docs.
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 * @since           1.0.0
 */

namespace Ultra\UltraConfigManager\Services;

// Import necessari
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Exceptions\PersistenceException;
use Psr\Log\LoggerInterface; // Importa LoggerInterface
use Illuminate\Database\QueryException;
use Throwable;
use InvalidArgumentException; // Importa InvalidArgumentException

/**
 * 🎯 Purpose: Encapsulates the logic for creating and calculating configuration version
 *    records (`uconfig_versions`). It ensures versions are created correctly based on
 *    the current state of a configuration entry and calculates the next sequential version number.
 *
 * 🧱 Structure:
 *    - Contains methods `createVersion` and `getNextVersion`.
 *    - Relies on Eloquent models (`UltraConfigVersion`, `UltraConfigModel`).
 *    - Injects `LoggerInterface` for logging activities and errors.
 *
 * 🧩 Context: Instantiated by the Service Provider and typically injected into `EloquentConfigDao`
 *    to handle version creation during save operations. `getNextVersion` might be used elsewhere
 *    if needed, but `createVersion` is its primary responsibility in the UCM flow.
 *
 * 🛠️ Usage:
 *    - `$nextVer = $versionManager->getNextVersion($configId);`
 *    - `$versionModel = $versionManager->createVersion($configModel, $userId, $sourceFile);` (Called by DAO)
 *
 * 💾 State: Interacts with the `uconfig_versions` database table via `UltraConfigVersion` model.
 *
 * 🗝️ Key Methods:
 *    - `createVersion`: Calculates the next version number and creates the corresponding record.
 *    - `getNextVersion`: Calculates the next version number based on existing ones for a given config ID.
 *
 * 🚦 Signals:
 *    - `createVersion`: Returns `UltraConfigVersion` model instance on success. Throws `PersistenceException`.
 *    - `getNextVersion`: Returns `int` (next version number). Throws `InvalidArgumentException`, `PersistenceException`.
 *    - Logs: Actions and errors via `LoggerInterface`.
 *
 * 🛡️ Privacy (GDPR):
 *    - `@privacy-internal`: `createVersion` persists configuration values (relies on model encryption) and metadata (`userId`, `sourceFile`) into version history.
 *    - `@privacy-delegated`: Relies on `UltraConfigVersion` model for potential encryption of the `value` field.
 *
 * 🤝 Dependencies:
 *    - `LoggerInterface` (ULM).
 *    - Eloquent Models: `UltraConfigModel`, `UltraConfigVersion`.
 *    - Exceptions: `PersistenceException`, `InvalidArgumentException`.
 *
 * 🧪 Testing:
 *    - Unit Test: Mock `UltraConfigVersion` model. Verify `createVersion` calls `getNextVersion` and `UltraConfigVersion::create()` with correct data. Mock logger. Test `getNextVersion` logic (max query).
 *    - Integration Test: Use `RefreshDatabase`. Test `getNextVersion` returns correct sequence. Test `createVersion` creates records with correct data and incrementing versions. Test exception handling.
 *
 * 💡 Logic:
 *    - `getNextVersion`: Queries `uconfig_versions` for `MAX(version)` for the specific `uconfig_id` and returns `max + 1`. Handles `null` case (returns 1).
 *    - `createVersion`: Calls `getNextVersion`, prepares data array using info from the provided `UltraConfigModel`, and calls `UltraConfigVersion::create()`. Wraps in try-catch for persistence errors.
 *
 * @package Ultra\UltraConfigManager\Services
 */
class VersionManager
{
    /** @var LoggerInterface The injected PSR-3 logger. */
    protected readonly LoggerInterface $logger; // Aggiungi la proprietà logger

    /**
     * 🎯 Constructor: Injects necessary dependencies.
     *
     * @param LoggerInterface $logger PSR-3 logger instance (ULM).
     */
    public function __construct(LoggerInterface $logger) // Aggiungi Logger al costruttore
    {
        $this->logger = $logger; // Assegna il logger
        $this->logger->debug('VersionManager initialized.');
    }

    /**
     * 🎯 Calculates the next sequential version number for a given configuration ID.
     * Retrieves the highest existing version number from the database for the specified
     * config ID and returns the next integer in the sequence (starting from 1).
     *
     * @param int $configId The unique identifier of the configuration entry. Must be positive.
     * @return int The next available version number (>= 1).
     *
     * @throws InvalidArgumentException If `$configId` is not a positive integer.
     * @throws PersistenceException If a database error occurs while querying versions.
     * @query Performs a MAX() aggregate query on the `uconfig_versions` table filtered by `uconfig_id`.
     */
    public function getNextVersion(int $configId): int // Metodo esistente (leggermente commentato)
    {
        // --- Input Validation ---
        if ($configId <= 0) {
            // Logga l'errore prima di lanciare l'eccezione
            $this->logger->error('VersionManager: Invalid config ID provided for getNextVersion.', ['config_id' => $configId]);
            throw new InvalidArgumentException("Configuration ID must be a positive integer. Received: {$configId}");
        }
        $this->logger->debug('VersionManager: Calculating next version number.', ['config_id' => $configId]);

        // --- Database Query ---
        try {
            // Find the maximum existing version for this specific config ID.
            $latestVersion = UltraConfigVersion::where('uconfig_id', $configId)->max('version');

            // Calculate the next version. If null (no previous versions), start at 1.
            $nextVersion = ($latestVersion ?? 0) + 1;

            $this->logger->debug('VersionManager: Next version number calculated.', ['config_id' => $configId, 'latest_found' => $latestVersion, 'next_version' => $nextVersion]);
            return $nextVersion;

        } catch (QueryException $e) {
             $this->logger->error('VersionManager: Database error calculating next version number.', ['config_id' => $configId, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
            // Wrap DB specific exception into our domain exception
            throw new PersistenceException(
                "Database error calculating next version for config ID '{$configId}'.",
                (int)$e->getCode(), // Cast code to int
                $e
            );
        } catch (Throwable $e) {
            // Catch any other unexpected errors during the process
            $this->logger->error('VersionManager: Unexpected error calculating next version number.', ['config_id' => $configId, 'exception' => $e::class, 'message' => $e->getMessage()]);
             if ($e instanceof InvalidArgumentException) throw $e; // Re-throw original validation exception
             throw new PersistenceException(
                "Unexpected error calculating next version for config ID '{$configId}'.",
                0, // Use 0 for unexpected error code unless $e provides one
                $e
            );
        }
    }

    /**
     * 🎯 Creates a new version record for a given configuration model.
     * Calculates the next version number and persists the new version entry using data from the provided model.
     *
     * @param UltraConfigModel $config The configuration model instance whose current state needs to be versioned.
     * @param int|null $userId The ID of the user triggering the version creation (null for system). @privacy-internal
     * @param string|null $sourceFile The source file or 'manual' indicator for this version. @privacy-internal
     * @return UltraConfigVersion The newly created version model instance.
     * @throws PersistenceException If calculating the next version or creating the record fails.
     * @throws InvalidArgumentException If the provided `$config->id` is invalid (delegated to `getNextVersion`).
     */
    public function createVersion(UltraConfigModel $config, ?int $userId, ?string $sourceFile): UltraConfigVersion
    {
        // Usa l'ID dal modello fornito
        $configId = $config->id;
        if (!$configId) {
             $this->logger->error('VersionManager: Cannot create version for unsaved UltraConfigModel (ID missing).');
             throw new InvalidArgumentException('Cannot create version for an unsaved UltraConfigModel instance.');
        }

        $this->logger->debug('VersionManager: Attempting to create version.', [
            'config_id' => $configId,
            'key' => $config->key,
            'userId' => $userId,
            'source_file' => $sourceFile
        ]);

        try {
            // 1. Calculate the next version number using the existing method
            $nextVersionNumber = $this->getNextVersion($configId);

            // 2. Prepare data for the new version record
            //    Ensure UltraConfigVersion model has 'user_id' and 'source_file' in $fillable
            //    and the columns exist and are nullable in the migration.
            $versionData = [
                'uconfig_id' => $configId,
                'version' => $nextVersionNumber,
                'key' => $config->key, // Copy key from parent
                'category' => $config->category?->value, // Copy category value
                'note' => $config->note, // Copy note
                'value' => $config->value, // Copy current value (assumes encryption handled by Version model if needed)
                'user_id' => $userId,     // Store the user ID
                'source_file' => $sourceFile, // Store the source file
            ];

            // 3. Create the version record using Eloquent
            $version = UltraConfigVersion::create($versionData);

            $this->logger->info('VersionManager: Version record created successfully.', [
                'version_id' => $version->id,
                'config_id' => $configId,
                'version_number' => $nextVersionNumber
            ]);

            return $version;

        } catch (QueryException $e) { // Catch DB errors during create()
            $this->logger->error('VersionManager: Database error creating version record.', ['config_id' => $configId, 'error' => $e->getMessage(), 'sql_code' => $e->getCode()]);
            throw new PersistenceException("Error creating version record for config ID '{$configId}'.", (int)$e->getCode(), $e);
        } catch (Throwable $e) {
            // Catch PersistenceException from getNextVersion or other errors
            $this->logger->error('VersionManager: Unexpected error creating version record.', ['config_id' => $configId, 'exception' => $e::class, 'message' => $e->getMessage()]);
             if ($e instanceof PersistenceException || $e instanceof InvalidArgumentException) {
                 throw $e; // Re-throw specific exceptions from getNextVersion or validation
             }
             // Wrap other unexpected errors
             throw new PersistenceException("Unexpected error creating version record for config ID '{$configId}'.", 0, $e);
        }
    }
} // Fine Classe VersionManager