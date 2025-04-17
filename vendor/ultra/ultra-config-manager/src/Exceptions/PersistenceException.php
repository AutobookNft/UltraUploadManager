<?php

/**
 * 📜 Oracode Exception: PersistenceException
 *
 * @package         Ultra\UltraConfigManager\Exceptions
 * @version         1.0.0
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Exceptions;

use RuntimeException; // Estende un'eccezione PHP standard
use Throwable;

/**
 * 🎯 Purpose: Represents a generic error that occurred during a data persistence
 *    operation within the UltraConfigManager DAO layer (e.g., database read/write failure).
 *    Serves as the base class for more specific persistence-related exceptions.
 *
 * 🧱 Structure: Extends PHP's `RuntimeException`. Can hold an optional previous exception
 *    for error chaining and context preservation.
 *
 * 🧩 Context: Thrown by DAO implementations (like `EloquentConfigDao`) when interactions
 *    with the underlying data store fail for reasons other than specific conditions like
 *    "not found" or "duplicate key".
 *
 * 🛠️ Usage: Caught by callers of the DAO (like `UltraConfigManager`) or potentially by
 *    application-level error handlers (which might use `UltraErrorManager` to process it).
 *
 * 🚦 Signals: Indicates a failure in storing or retrieving configuration data.
 *
 * 🤝 Dependencies: None beyond standard PHP exception handling.
 *
 * @package Ultra\UltraConfigManager\Exceptions
 */
class PersistenceException extends RuntimeException
{
    /**
     * 🎯 Constructor: Creates a new PersistenceException instance.
     *
     * @param string $message The exception message.
     * @param int $code The exception code (often inherited from DB driver). Defaults to 0.
     * @param ?Throwable $previous The previous exception used for chaining. Defaults to null.
     */
    public function __construct(string $message = "An error occurred during data persistence.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}