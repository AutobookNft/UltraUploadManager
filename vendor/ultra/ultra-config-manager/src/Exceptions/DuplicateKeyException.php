<?php

/**
 * 📜 Oracode Exception: DuplicateKeyException
 * Exception indicating an attempt to violate a unique key constraint in UCM.
 *
 * @package         Ultra\UltraConfigManager\Exceptions
 * @version         1.0.1 // Updated to Oracode v1.5.0 documentation standard.
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 * @since           1.0.0
 */

namespace Ultra\UltraConfigManager\Exceptions;

use Throwable;
// use Ultra\UltraConfigManager\Exceptions\PersistenceException; // Import if not in same namespace

/**
 * 🎯 Purpose: Signals that an attempt was made to create or update a configuration entry
 *    in a way that violates a unique key constraint defined in the persistence layer (typically the database).
 *    This specifically targets errors arising from duplicate 'key' values.
 *
 * 🧱 Structure:
 *    - Extends `PersistenceException`, inheriting its base behavior for persistence-related errors.
 *    - Overrides the constructor to provide a default message specific to duplicate key scenarios.
 *    - Focuses on semantic differentiation from other persistence failures (e.g., connection errors, not found).
 *
 * 🧩 Context: Primarily thrown by `ConfigDaoInterface` implementations (like `EloquentConfigDao`)
 *    within methods responsible for creating or potentially updating records (`saveConfig`).
 *    It typically originates from catching a database-level exception (like PDOException with SQLSTATE 23000
 *    or a specific QueryException indicating a unique constraint violation) and re-throwing it
 *    as this more specific, domain-relevant exception.
 *
 * 🛠️ Usage: Allows callers (e.g., Controllers or Services using `UltraConfigManager`) to specifically
 *    catch this exception and provide targeted user feedback, such as "This configuration key is already in use."
 *    ```php
 *    try {
 *        $ucm->set('duplicate.key', 'value', 'system', $userId);
 *    } catch (DuplicateKeyException $e) {
 *        // Handle the duplicate key error specifically
 *        return back()->withErrors(['key' => 'This configuration key already exists. Please choose another.']);
 *    } catch (PersistenceException $e) {
 *        // Handle other database errors during the save operation
 *        Log::error("Failed to save configuration: " . $e->getMessage());
 *        return back()->with('error', 'Could not save configuration due to a database issue.');
 *    }
 *    ```
 *
 * 🚦 Signals:
 *    - Explicitly indicates a **data integrity violation** due to a duplicate unique key ('key' column).
 *    - Inherits the signal of a persistence-related issue from `PersistenceException`.
 *
 * 🤝 Dependencies:
 *    - PHP `Throwable` interface.
 *    - `Ultra\UltraConfigManager\Exceptions\PersistenceException` (Parent class).
 *
 * 🛡️ Privacy (GDPR): Low direct impact. The exception message should generally avoid echoing back
 *    potentially sensitive *values* that caused the conflict, focusing only on the key name if necessary,
 *    although the default message is generic.
 *
 * 🧪 Testing:
 *    - Create a test scenario where a configuration key is inserted twice.
 *    - Verify that the DAO catches the underlying database exception (e.g., PDOException, QueryException).
 *    - Verify that the DAO correctly re-throws a `DuplicateKeyException`.
 *    - Test `catch` blocks specifically designed for `DuplicateKeyException`.
 *
 * 💡 Logic: Simple extension of `PersistenceException` to semantically represent unique constraint violations related to the configuration 'key'. Improves error handling clarity.
 *
 * @package Ultra\UltraConfigManager\Exceptions
 */
class DuplicateKeyException extends PersistenceException
{
    /**
     * 🎯 Constructor: Initializes the exception with a context-specific message for duplicate keys.
     * Provides a clear default message, while allowing customization and preserving the original
     * database exception for debugging root causes.
     *
     * @param string $message Descriptive error message indicating the duplicate key issue. Defaults to a generic message.
     * @param int $code Standard exception code, often the SQLSTATE code (like 23000) from the underlying database error. Defaults to 0.
     * @param ?Throwable $previous The previous database exception (e.g., `PDOException`, `QueryException`) that triggered this condition. Defaults to null.
     */
    public function __construct(string $message = "A configuration with the provided key already exists.", int $code = 0, ?Throwable $previous = null)
    {
        // Call the parent constructor (PersistenceException) to maintain the exception chain and standard properties.
        parent::__construct($message, $code, $previous);
    }
}