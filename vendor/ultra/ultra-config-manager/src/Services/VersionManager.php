<?php

/**
 * 📜 Oracode Service: VersionManager
 *
 * @package         Ultra\UltraConfigManager\Services
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Services;

use Illuminate\Database\QueryException;
use InvalidArgumentException; // PHP Standard Exception
use Throwable; // Per catturare eccezioni generiche
use Ultra\UltraConfigManager\Exceptions\PersistenceException; // Custom Exception
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
// Non più necessario UltraLog Facade
// Non necessaria LoggerInterface qui, l'errore è segnalato dall'eccezione

/**
 * 🎯 Purpose: Calculates the next available sequential version number for a given
 *    configuration entry based on its existing version history in the database.
 *    Ensures version numbers are always positive and incremental.
 *
 * 🧱 Structure: Contains a single public method `getNextVersion`. Relies on the
 *    `UltraConfigVersion` Eloquent model to query the database.
 *
 * 🧩 Context: Typically used internally by DAO implementations (like `EloquentConfigDao`)
 *    within a database transaction when creating a new version record for a configuration.
 *
 * 🛠️ Usage: `$versionManager->getNextVersion($configId);`
 *
 * 💾 State: Stateless service. Does not hold internal state between calls. Reads from DB.
 *
 * 🗝️ Key Methods:
 *    - `getNextVersion`: The primary method performing the version calculation.
 *
 * 🚦 Signals:
 *    - Returns `int`: The calculated next version number (always >= 1).
 *    - Throws `InvalidArgumentException`: If the provided `$configId` is not positive.
 *    - Throws `PersistenceException`: If a database error occurs during the query.
 *
 * 🛡️ Privacy (GDPR): This service handles only the `configId` (an internal identifier,
 *    typically non-PII) and version numbers. It does not directly process sensitive data.
 *    - `@privacy-safe`: Operates on non-sensitive identifiers and counters.
 *
 * 🤝 Dependencies:
 *    - `UltraConfigVersion` Eloquent Model: To query the `uconfig_versions` table.
 *    - (Implicit) Laravel Database connection.
 *
 * 🧪 Testing:
 *    - Unit Test: Can be tested directly. Mocking the static `::where()` call on `UltraConfigVersion`
 *      is possible using tools like Mockery/Prophecy if needed, or test against an in-memory DB.
 *    - Test cases:
 *        - Input `configId` <= 0 throws `InvalidArgumentException`.
 *        - Config with no previous versions returns 1.
 *        - Config with existing versions returns `max(version) + 1`.
 *        - Database query failure throws `PersistenceException`.
 *
 * 💡 Logic:
 *    - Validates input `configId`.
 *    - Queries the `uconfig_versions` table for the maximum `version` associated with the `configId`.
 *    - If no versions exist (`max` returns null), the next version is 1.
 *    - Otherwise, the next version is the maximum found plus one.
 *    - Wraps database interaction in try-catch to handle potential `QueryException` or other errors.
 *
 * @package Ultra\UltraConfigManager\Services
 */
class VersionManager
{
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
     * @query Performs a MAX() aggregate query on the versions table.
     */
    public function getNextVersion(int $configId): int
    {
        // --- Input Validation ---
        if ($configId <= 0) {
            // No logging here needed, exception is the signal
            throw new InvalidArgumentException("Configuration ID must be a positive integer. Received: {$configId}");
        }

        // --- Database Query ---
        try {
            // Find the maximum existing version number for this specific config ID.
            // `max()` returns null if no records match or the column contains only nulls.
            $latestVersion = UltraConfigVersion::where('uconfig_id', $configId)->max('version');

            // Calculate the next version. If null (no previous versions), start at 1.
            $nextVersion = ($latestVersion ?? 0) + 1;

            // Optional: Logging could be added here if needed, but primarily relies on exceptions for errors.
            // $this->logger->debug('Version calculated.', ['config_id' => $configId, 'latest' => $latestVersion, 'next' => $nextVersion]);

            return $nextVersion;

        } catch (QueryException $e) {
            // Wrap DB specific exception into our domain exception
            throw new PersistenceException(
                "Database error calculating next version for config ID '{$configId}'.",
                $e->getCode(),
                $e
            );
        } catch (Throwable $e) {
            // Catch any other unexpected errors during the process
             if ($e instanceof InvalidArgumentException) throw $e; // Rilancia eccezione di validazione originale
             throw new PersistenceException(
                "Unexpected error calculating next version for config ID '{$configId}'.",
                0, // Use 0 for unexpected error code unless $e provides one
                $e
            );
        }
    }
}