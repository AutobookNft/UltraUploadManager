<?php

/**
 * 📜 Oracode Exception: DuplicateKeyException
 *
 * @package         Ultra\UltraConfigManager\Exceptions
 * @version         1.0.0
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Exceptions;

use Throwable;

/**
 * 🎯 Purpose: Represents an error caused by attempting to create a configuration entry
 *    with a key that already exists and violates a unique constraint in the persistence layer.
 *
 * 🧱 Structure: Extends `PersistenceException` to provide specific semantic meaning related
 *    to data integrity violations.
 *
 * 🧩 Context: Thrown by DAO implementations (specifically the `saveConfig` method when creating)
 *    if the underlying database signals a unique key constraint violation.
 *
 * 🛠️ Usage: Can be specifically caught by callers (like a Controller) to provide user-friendly
 *    feedback about the duplicate key attempt, distinct from other save errors.
 *
 * 🚦 Signals: Indicates a data integrity conflict due to a duplicate unique key.
 *
 * 🤝 Dependencies: Inherits from `PersistenceException`.
 *
 * @package Ultra\UltraConfigManager\Exceptions
 */
class DuplicateKeyException extends PersistenceException
{
    /**
     * 🎯 Constructor: Creates a new DuplicateKeyException instance.
     *
     * @param string $message The exception message. Defaults to a standard "duplicate key" message.
     * @param int $code The exception code (often inherited from DB driver). Defaults to 0.
     * @param ?Throwable $previous The previous exception (e.g., a PDOException or QueryException indicating the constraint violation). Defaults to null.
     */
    public function __construct(string $message = "A configuration with the provided key already exists.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}