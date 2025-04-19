<?php

/**
 * 📜 Oracode Cast: EncryptedCast
 * Handles transparent encryption/decryption for Eloquent attributes,
 * including serialization for non-string types.
 *
 * @package         Ultra\UltraConfigManager\Casts
 * @version         1.2.0 // Added JSON serialization/deserialization, Oracode v1.5.0 docs, refined error handling.
 * @author          Fabio Cherici (Original), Padmin D. Curtis (Refactoring)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 * @since           1.0.0
 */

namespace Ultra\UltraConfigManager\Casts; // O il tuo namespace corretto

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\Encrypter; // Use Laravel's Encrypter Contract
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Psr\Log\LoggerInterface; // Use PSR-3 interface
use Throwable;
use InvalidArgumentException; // Standard PHP Exception
use RuntimeException;         // Standard PHP Exception

/**
 * 🎯 Purpose: Provides automatic, transparent encryption and decryption for Eloquent model attributes.
 *    Ensures that potentially sensitive configuration values (strings, numbers, booleans, arrays)
 *    are encrypted using Laravel's encryption service (`APP_KEY`) before being stored in the database
 *    and automatically decrypted/deserialized when accessed via the Eloquent model.
 *
 * 🧱 Structure:
 *    - Implements Laravel's `CastsAttributes` interface.
 *    - `set()` method: Serializes non-string scalar values and arrays into JSON, then encrypts the resulting string. Handles null values. Throws exceptions on failure or unsupported types.
 *    - `get()` method: Decrypts the string retrieved from the database, attempts to JSON decode it, and returns the original value (string, array, int, bool, null). Handles null values and decryption/decoding errors. Throws exception on decryption failure.
 *    - Uses helper methods (`resolveEncrypter`, `resolveLogger`) to get dependencies from the container, avoiding direct Facade usage.
 *
 * 🧩 Context: Applied within the `$casts` property of an Eloquent model (e.g., `UltraConfigModel`,
 *    `UltraConfigVersion`, `UltraConfigAudit`) to attributes requiring encryption at rest
 *    (like `value`, `old_value`, `new_value`). Assumes Laravel's encryption service is configured.
 *
 * 🛠️ Usage:
 *    In Eloquent Model:
 *    ```php
 *    use Ultra\UltraConfigManager\Casts\EncryptedCast;
 *
 *    protected $casts = [
 *        'value' => EncryptedCast::class,
 *        'old_value' => EncryptedCast::class, // Apply to audit/version values too
 *        'new_value' => EncryptedCast::class,
 *        // ... other casts ...
 *    ];
 *    ```
 *
 * 💾 State: Stateless. Operates purely on the input value during the Eloquent attribute lifecycle.
 *
 * 🗝️ Key Methods:
 *    - `get`: Decrypts value from DB, attempts JSON decode, returns original type.
 *    - `set`: Serializes value to JSON (if needed), encrypts string for DB storage.
 *
 * 🚦 Signals:
 *    - `get` returns: Decrypted/deserialized value (mixed type), or null.
 *    - `get` throws: `RuntimeException` on decryption failure.
 *    - `set` returns: Encrypted string or null.
 *    - `set` throws: `InvalidArgumentException` for unsupported types or JSON encoding errors.
 *    - `set` throws: `RuntimeException` on encryption failure.
 *    - Logs: Errors during encryption, decryption, or logger resolution via injected `LoggerInterface`.
 *
 * 🛡️ Privacy (GDPR):
 *    - `@privacy-feature`: Central component for ensuring encryption at rest for sensitive configuration.
 *    - `@privacy-internal`: Handles the intermediate plain text data during the get/set process.
 *    - `@privacy-risk`: Decryption failure (e.g., due to `APP_KEY` change or data corruption) now throws a `RuntimeException`, preventing the application from potentially using invalid/encrypted data silently. Requires proper handling by the calling code. Relies on secure management of `APP_KEY`.
 *
 * 🤝 Dependencies:
 *    - `Illuminate\Contracts\Database\Eloquent\CastsAttributes`: Interface implemented.
 *    - `Illuminate\Contracts\Encryption\Encrypter`: Laravel's encryption service contract.
 *    - `Psr\Log\LoggerInterface`: PSR-3 Logger contract (ULM).
 *    - Exceptions: `DecryptException`, `EncryptException`, `InvalidArgumentException`, `RuntimeException`.
 *
 * 🧪 Testing:
 *    - Integration Test: Use a model with the cast applied. Save various data types (string, int, bool, null, array). Assert that the value in the database is a non-null string (encrypted) and not the plain value. Retrieve the model and assert that the accessed attribute matches the original value and type. Test `null` handling. Test behavior when `APP_KEY` changes (should throw `RuntimeException` on `get`).
 *    - Unit Test: Mock the `Encrypter` contract. Test the `set` method: verify `json_encode` is called for arrays/scalars (except string), verify `encryptString` is called with the correct string, verify `null` is returned for `null` input, verify exceptions for unsupported types. Test the `get` method: verify `decryptString` is called, test `json_decode` logic for both JSON and plain strings, test `null` handling, test exception wrapping for `DecryptException`.
 *
 * 💡 Logic:
 *    - `set`: Check type -> JSON encode if needed -> Encrypt string -> Return encrypted string.
 *    - `get`: Decrypt string -> Attempt JSON decode -> Return decoded value or original decrypted string if decode fails -> Handle nulls and errors.
 *
 * @package Ultra\UltraConfigManager\Casts
 */
class EncryptedCast implements CastsAttributes
{
    /**
     * 🔒 Decrypts the attribute's value when accessed on the model.
     * Attempts to JSON decode the decrypted string to restore original types (arrays, bools, numbers).
     *
     * @param \Illuminate\Database\Eloquent\Model $model The Eloquent model instance.
     * @param string $key The attribute key being accessed.
     * @param mixed $value The raw, potentially encrypted string value from the database.
     * @param array<string, mixed> $attributes All raw attributes retrieved from the database.
     * @return mixed The decrypted and potentially deserialized value (string, array, bool, int, float, null).
     * @throws RuntimeException If decryption fails (e.g., invalid key, corrupted data).
     * @readOperation Decrypts data and potentially deserializes JSON.
     * @log Logs decryption errors via injected logger.
     */
    public function get($model, string $key, $value, array $attributes): mixed
    {
        // Handle null value directly
        if ($value === null) {
            return null;
        }

        // Ensure the value from DB is a string before attempting decryption
        if (!is_string($value)) {
            $this->resolveLogger()?->warning('EncryptedCast: Non-string value encountered in database for encrypted attribute, returning as is.', [
                'key' => $key, 'model' => get_class($model), 'model_id' => $model->getKey(), 'type_found' => gettype($value)
            ]);
            return $value; // Or should this be an error? Returning as is for now.
        }

        try {
            $encrypter = $this->resolveEncrypter();
            $decryptedString = $encrypter->decryptString($value);

            // Attempt to decode JSON - handles arrays, bools, numbers, null stored as JSON
            $decodedJson = json_decode($decryptedString, true); // Use true for associative array

            if (json_last_error() === JSON_ERROR_NONE) {
                // Successfully decoded JSON - return the original type
                $this->resolveLogger()?->debug('EncryptedCast: Decrypted and JSON decoded value.', ['key' => $key, 'type' => gettype($decodedJson)]);
                return $decodedJson;
            } else {
                // If JSON decoding failed, it was likely a simple string that was encrypted
                $this->resolveLogger()?->debug('EncryptedCast: Decrypted string value (was not JSON).', ['key' => $key]);
                return $decryptedString;
            }
        } catch (DecryptException $e) {
            $this->resolveLogger()?->error('EncryptedCast: Failed to decrypt attribute. Check APP_KEY and data integrity.', [
                'key' => $key,
                'model' => get_class($model),
                'model_id' => $model->getKey(),
                'error' => $e->getMessage()
            ]);
            // Throw a runtime exception to clearly signal decryption failure
            throw new RuntimeException("Failed to decrypt attribute '{$key}' for model " . get_class($model) . " [ID: {$model->getKey()}].", 0, $e);
        } catch (Throwable $e) {
             $this->resolveLogger()?->error('EncryptedCast: Unexpected error during decryption.', [
                 'key' => $key, 'model' => get_class($model), 'model_id' => $model->getKey(),
                 'exception' => $e::class, 'error' => $e->getMessage(),
             ]);
             // Re-throw wrapped exception
             throw new RuntimeException("Unexpected error decrypting attribute '{$key}' for model " . get_class($model) . ".", 0, $e);
        }
    }

    /**
     * 🔒 Encrypts the attribute's value before saving it to the database.
     * Automatically serializes arrays and non-string scalar types to JSON before encryption.
     *
     * @param \Illuminate\Database\Eloquent\Model $model The Eloquent model instance.
     * @param string $key The attribute key being set.
     * @param mixed $value The plain value to be encrypted (string, array, bool, int, float, null).
     * @param array<string, mixed> $attributes The current attributes being set on the model.
     * @return string|null The encrypted string representation of the value, or null if the input value was null.
     * @throws InvalidArgumentException If the value type is unsupported (e.g., object, resource) or JSON encoding fails.
     * @throws RuntimeException If encryption fails.
     * @writeOperation Serializes (if needed) and encrypts data.
     * @log Logs serialization actions and encryption errors.
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        // Handle null value directly
        if ($value === null) {
            return null;
        }

        // --- Serialize to String (JSON Encoding) ---
        $stringToEncrypt = '';
        $type = gettype($value);

        if ($type === 'string') {
            $stringToEncrypt = $value;
            $this->resolveLogger()?->debug('EncryptedCast: Encrypting raw string.', ['key' => $key]);
        } elseif (is_scalar($value) || $type === 'array') { // Includes bool, int, float, array
            // Use json_encode for consistent serialization of scalars and arrays
            $stringToEncrypt = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // Add flags for better JSON
            if ($stringToEncrypt === false) {
                 $jsonError = json_last_error_msg();
                 $this->resolveLogger()?->error('EncryptedCast: Failed to JSON encode value before encryption.', ['key' => $key, 'type' => $type, 'json_error' => $jsonError]);
                 throw new InvalidArgumentException("Cannot JSON encode value (type: {$type}) for encryption for key '{$key}'. Error: {$jsonError}");
            }
            $this->resolveLogger()?->debug('EncryptedCast: Value JSON encoded before encryption.', ['key' => $key, 'type' => $type]);
        } else {
            // Unsupported type (objects, resources, etc.)
             $this->resolveLogger()?->error('EncryptedCast: Unsupported data type provided for encryption.', ['key' => $key, 'type' => $type]);
             throw new InvalidArgumentException("Unsupported data type provided for encrypted attribute '{$key}'. Cannot encrypt type {$type}.");
        }
        // --- End Serialization ---

        // --- Encrypt the String ---
        try {
            $encrypter = $this->resolveEncrypter();
            $encryptedValue = $encrypter->encryptString($stringToEncrypt);
             $this->resolveLogger()?->debug('EncryptedCast: Value encrypted successfully.', ['key' => $key]);
            return $encryptedValue;
        } catch (EncryptException $e) {
            $this->resolveLogger()?->error('EncryptedCast: Failed to encrypt attribute.', [
                'key' => $key, 'model' => get_class($model), 'model_id' => $model->getKey(),
                'exception' => $e::class, 'error' => $e->getMessage()
            ]);
            // Re-throw as RuntimeException to signal encryption failure clearly
            throw new RuntimeException("Failed to encrypt attribute '{$key}' for model " . get_class($model) . ".", 0, $e);
        } catch (Throwable $e) {
             $this->resolveLogger()?->error('EncryptedCast: Unexpected error during encryption.', [
                 'key' => $key, 'model' => get_class($model), 'model_id' => $model->getKey(),
                 'exception' => $e::class, 'error' => $e->getMessage(),
             ]);
              // Re-throw wrapped exception
             throw new RuntimeException("Unexpected error encrypting attribute '{$key}' for model " . get_class($model) . ".", 0, $e);
        }
    }

    /**
     * 🏭 Resolves the Encrypter contract instance from the Laravel service container.
     * Uses `app()` helper for brevity and common practice within Laravel contexts.
     * @internal Helper method for `get` and `set`.
     * @return Encrypter Instance of the Encrypter service.
     * @throws RuntimeException If the Encrypter contract cannot be resolved (indicates a fundamental framework issue).
     */
    protected function resolveEncrypter(): Encrypter
    {
        try {
            // Prefer resolving via the specific contract interface
            return app(Encrypter::class);
        } catch (Throwable $e) {
             $logMessage = 'EncryptedCast: Could not resolve Encrypter contract from container. Ensure encryption services are configured.';
             // Log using error_log as a last resort if logger isn't available/fails
             error_log($logMessage . ' Error: ' . $e->getMessage());
             // Throw a clear exception indicating a setup problem
             throw new RuntimeException($logMessage, 0, $e);
        }
    }

    /**
     * 📝 Safely resolves a Logger instance from the Laravel service container.
     * Returns null if the logger cannot be resolved, allowing the cast to function
     * without logging in edge cases but logs an error via `error_log`.
     * @internal Helper method.
     * @return LoggerInterface|null The resolved logger instance or null.
     */
    protected function resolveLogger(): ?LoggerInterface
    {
        // Use try-catch to handle potential issues resolving the logger itself
        try {
            // Check if the LoggerInterface is bound before trying to make it
            if (function_exists('app') && app()->bound(LoggerInterface::class)) {
                return app(LoggerInterface::class);
            }
        } catch (Throwable $e) {
            // Fallback to error_log if logger resolution fails
            error_log('EncryptedCast: Failed to resolve LoggerInterface: ' . $e->getMessage());
        }
        // Return null if resolution failed or container/binding not available
        return null;
    }
}