<?php

/**
 * 📜 Oracode Exception: ConfigNotFoundException
 * Exception specific for configuration not found errors within UCM.
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
// Ensure PersistenceException is imported if not in the same namespace
// use Ultra\UltraConfigManager\Exceptions\PersistenceException;

/**
 * 🎯 Purpose: Signals that a specifically requested configuration entry
 *    (identified by key or ID) could not be located within the UCM persistence layer (database/cache).
 *    This provides a semantically distinct error compared to general database connection
 *    or query failures handled by `PersistenceException`.
 *
 * 🧱 Structure:
 *    - Extends `PersistenceException`, inheriting its base behavior for persistence-related errors.
 *    - Overrides the constructor to provide a default message specific to "not found" scenarios.
 *    - Does not add new properties or methods, focuses on semantic differentiation.
 *
 * 🧩 Context: Thrown primarily by `ConfigDaoInterface` implementations (like `EloquentConfigDao`)
 *    when methods like `getConfigById` or `getConfigByKey` fail to find a matching record.
 *    It is also thrown by `UltraConfigManager::getOrFail()` when a required key is missing.
 *    It is intended to be caught by higher-level services or controllers that need to
 *    distinguish between a non-existent configuration and other data access problems.
 *
 * 🛠️ Usage:
 *    ```php
 *    try {
 *        $value = $ucm->getOrFail('required.setting');
 *        // ... use value ...
 *    } catch (ConfigNotFoundException $e) {
 *        // Handle the specific "not found" case, e.g., use a default, log, or return error response.
 *        Log::warning("Required setting 'required.setting' not found, using fallback.");
 *        // ... fallback logic ...
 *    } catch (PersistenceException $e) {
 *        // Handle other database-related errors during config access.
 *        Log::error("Database error accessing configuration: " . $e->getMessage());
 *        // ... general error handling ...
 *    }
 *    ```
 *
 * 🚦 Signals:
 *    - Explicitly indicates that the requested configuration key/ID does **not exist** in the configured store.
 *    - Inherits the signal of a persistence-related issue from `PersistenceException`.
 *
 * 🤝 Dependencies:
 *    - PHP `Throwable` interface.
 *    - `Ultra\UltraConfigManager\Exceptions\PersistenceException` (Parent class).
 *
 * 🛡️ Privacy (GDPR): Generally low privacy impact itself, but the context in which it's thrown
 *    (e.g., trying to access a specific user's config) might have implications managed by the caller.
 *    The exception message should avoid exposing sensitive key names if possible, though the default is generic.
 *
 * 🧪 Testing:
 *    - Verify that DAO methods throw this specific exception when a record is not found (e.g., using `findOrFail` and catching `ModelNotFoundException`).
 *    - Verify that `UltraConfigManager::getOrFail` throws this exception for missing keys.
 *    - Test `catch` blocks specifically designed for `ConfigNotFoundException`.
 *
 * 💡 Logic: Simple extension of `PersistenceException` to provide a clear, specific meaning for "not found" errors within the UCM domain.
 *
 * @package Ultra\UltraConfigManager\Exceptions
 */
class ConfigNotFoundException extends PersistenceException
{
    /**
     * 🎯 Constructor: Initializes the exception with a context-specific message.
     * Provides a default message indicating the configuration was not found, while allowing
     * customization and preserving the previous exception chain for debugging.
     *
     * @param string $message Descriptive error message indicating which configuration was not found. Defaults to a generic message.
     * @param int $code Standard exception code (usually 0 for application exceptions unless mapping HTTP status). Defaults to 0.
     * @param ?Throwable $previous The previous exception in the chain (e.g., Eloquent's `ModelNotFoundException`), useful for tracing the root cause. Defaults to null.
     */
    public function __construct(string $message = "The requested configuration was not found.", int $code = 0, ?Throwable $previous = null)
    {
        // Call the parent constructor (PersistenceException) to maintain the exception chain and standard properties.
        parent::__construct($message, $code, $previous);
    }
}