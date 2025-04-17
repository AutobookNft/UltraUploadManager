<?php

/**
 * 📜 Oracode Exception: ConfigNotFoundException
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
 * 🎯 Purpose: Represents an error where a requested configuration entry could not be found
 *    in the persistence layer (e.g., lookup by ID or key failed).
 *
 * 🧱 Structure: Extends `PersistenceException` to provide specific semantic meaning.
 *
 * 🧩 Context: Thrown by DAO implementations when a specific configuration record is expected
 *    but is not present in the data store.
 *
 * 🛠️ Usage: Can be specifically caught by callers to handle "not found" scenarios distinctly
 *    from other persistence errors.
 *
 * 🚦 Signals: Indicates that the requested configuration does not exist.
 *
 * 🤝 Dependencies: Inherits from `PersistenceException`.
 *
 * @package Ultra\UltraConfigManager\Exceptions
 */
class ConfigNotFoundException extends PersistenceException
{
    /**
     * 🎯 Constructor: Creates a new ConfigNotFoundException instance.
     *
     * @param string $message The exception message. Defaults to a standard "not found" message.
     * @param int $code The exception code. Defaults to 0.
     * @param ?Throwable $previous The previous exception (e.g., Eloquent's ModelNotFoundException). Defaults to null.
     */
    public function __construct(string $message = "The requested configuration was not found.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}