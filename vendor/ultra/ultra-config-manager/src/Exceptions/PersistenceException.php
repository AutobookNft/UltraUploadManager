<?php

/**
 * 📜 Oracode Exception: PersistenceException
 * Base exception for data persistence errors within UCM's DAO layer.
 *
 * @package         Ultra\UltraConfigManager\Exceptions
 * @version         1.0.1 // Updated to Oracode v1.5.0 documentation standard.
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 * @since           1.0.0
 */

namespace Ultra\UltraConfigManager\Exceptions;

use RuntimeException; // Extends a standard PHP runtime exception
use Throwable;

/**
 * 🎯 Purpose: Represents a general failure during data persistence operations
 *    (read, write, delete) involving the configuration data store (e.g., database).
 *    It serves as the **base exception** for errors occurring within the Data Access Object (DAO)
 *    layer of UltraConfigManager, intended to be caught when specific details
 *    (like "not found" or "duplicate key") are not necessary or haven't been identified.
 *
 * 🧱 Structure:
 *    - Extends PHP's standard `RuntimeException`, indicating an error that occurred during runtime.
 *    - Provides a standard constructor accepting a message, code, and an optional previous `Throwable`
 *      to maintain the exception chain for debugging.
 *    - Designed to be extended by more specific persistence exceptions like `ConfigNotFoundException`
 *      and `DuplicateKeyException`.
 *
 * 🧩 Context: Typically thrown by `ConfigDaoInterface` implementations (e.g., `EloquentConfigDao`)
 *    when an underlying database operation (SELECT, INSERT, UPDATE, DELETE) fails due to issues like:
 *      - Database connection errors.
 *      - SQL query syntax errors.
 *      - Deadlocks or transaction failures.
 *      - Constraint violations *other than* unique key conflicts (handled by `DuplicateKeyException`).
 *      - Unexpected database driver errors.
 *    It can also be thrown by the `UltraConfigManager` itself if operations involving the DAO fail unexpectedly.
 *
 * 🛠️ Usage:
 *    - Can be caught as a general fallback when interacting with the `ConfigDaoInterface` or `UltraConfigManager`
 *      to handle any persistence-related failure gracefully.
 *    - More specific exceptions (`ConfigNotFoundException`, `DuplicateKeyException`) should be caught *before*
 *      catching `PersistenceException` if distinct handling is required.
 *    ```php
 *    try {
 *        $config = $dao->getConfigById(1);
 *        // ... or $ucm->set(...) ...
 *    } catch (ConfigNotFoundException $e) {
 *        // Handle "not found"
 *    } catch (DuplicateKeyException $e) {
 *        // Handle "duplicate key"
 *    } catch (PersistenceException $e) {
 *        // Handle any other database/persistence error during the operation
 *        Log::error("A general persistence error occurred: " . $e->getMessage());
 *        // Provide generic user feedback or trigger alerts
 *    }
 *    ```
 *
 * 🚦 Signals:
 *    - Indicates a generic failure in the **data persistence layer** during a configuration operation.
 *    - Suggests a potential issue with the database connection, query execution, or data integrity (other than specific known types).
 *
 * 🤝 Dependencies:
 *    - PHP `RuntimeException` (Parent class).
 *    - PHP `Throwable` interface (for previous exception).
 *
 * 🛡️ Privacy (GDPR): Low direct impact. However, the underlying database error message (`$previous` exception)
 *    might contain sensitive details (like table/column names) and should be logged carefully, avoiding direct
 *    exposure to end-users.
 *
 * 🧪 Testing:
 *    - Simulate database connection failures or invalid queries in DAO tests.
 *    - Verify that the DAO catches underlying driver exceptions (e.g., `PDOException`) and correctly re-throws them as `PersistenceException`.
 *    - Test generic `catch (PersistenceException $e)` blocks in services or controllers using the DAO/Manager.
 *
 * 💡 Logic: Acts as a fundamental error type for UCM's persistence layer, promoting consistent error handling by providing a common ancestor for all data store interaction failures.
 *
 * @package Ultra\UltraConfigManager\Exceptions
 */
class PersistenceException extends RuntimeException
{
    /**
     * 🎯 Constructor: Creates a new generic PersistenceException instance.
     * Used to signal a general failure during data persistence operations. Allows wrapping
     * the original low-level exception (e.g., PDOException) for better diagnostics.
     *
     * @param string $message Descriptive error message explaining the persistence failure. Defaults to a generic message.
     * @param int $code Standard exception code, potentially inherited from the underlying database driver error. Defaults to 0.
     * @param ?Throwable $previous The previous exception (e.g., `PDOException`, `QueryException`) that represents the root cause of the persistence failure. Defaults to null.
     */
    public function __construct(string $message = "An error occurred during data persistence.", int $code = 0, ?Throwable $previous = null)
    {
        // Call the parent (RuntimeException) constructor, passing along message, code, and previous exception.
        parent::__construct($message, $code, $previous);
    }
}