<?php

/**
 * 📜 Oracode Cast: EncryptedCast
 *
 * @package         Ultra\UltraConfigManager\Casts
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter; // Interfaccia per DI
use Psr\Log\LoggerInterface; // Per logging opzionale
use Throwable; // Per catturare eccezioni

/**
 * 🎯 Purpose: Provides automatic encryption and decryption for Eloquent model attributes.
 *    Ensures that sensitive data (like configuration values) stored in the database is
 *    encrypted at rest using Laravel's encryption service, and automatically decrypted
 *    when accessed through the model.
 *
 * 🧱 Structure: Implements Laravel's `CastsAttributes` interface. Defines `get` method for
 *    decryption and `set` method for encryption. Uses Laravel's `Encrypter` contract.
 *
 * 🧩 Context: Applied within the `$casts` property of an Eloquent model (e.g., `UltraConfigModel`,
 *    `UltraConfigVersion`, `UltraConfigAudit`) to specific attributes (`value`, `old_value`, `new_value`).
 *
 * 🛠️ Usage: In Model: `protected $casts = ['sensitive_field' => EncryptedCast::class];`
 *
 * 💾 State: Stateless. Operates on the value passed to it during the Eloquent get/set process.
 *
 * 🗝️ Key Methods:
 *    - `get`: Decrypts the value retrieved from the database. Handles decryption errors.
 *    - `set`: Encrypts the plain text value before it's stored in the database.
 *
 * 🚦 Signals:
 *    - `get` returns the decrypted value, null, or potentially the original encrypted value on decryption failure (with logging). Consider throwing exception instead.
 *    - `set` returns the encrypted string or null.
 *    - May log decryption errors.
 *
 * 🛡️ Privacy (GDPR):
 *    - Core component for GDPR compliance regarding data encryption at rest.
 *    - Encrypts data before it reaches the database.
 *    - Decrypts data only when accessed through the Eloquent model.
 *    - `@privacy-feature`: Provides transparent encryption/decryption.
 *    - `@privacy-internal`: Handles potentially sensitive data during encryption/decryption process.
 *    - `@privacy-risk`: If `APP_KEY` changes, decryption will fail. Current implementation logs the error and returns the encrypted string, which might hide the issue. Consider throwing a custom exception for failed decryption.
 *
 * 🤝 Dependencies:
 *    - `Illuminate\Contracts\Encryption\Encrypter`: Resolved via service container for performing encryption/decryption.
 *    - `Illuminate\Contracts\Encryption\DecryptException`: Caught during decryption attempts.
 *    - `Psr\Log\LoggerInterface` (Optional): Resolved via service container for logging decryption errors.
 *
 * 🧪 Testing:
 *    - Can be tested in integration with a model using an in-memory DB, verifying that values stored are not plain text and values retrieved match the original.
 *    - Mock the `Encrypter` contract in unit tests if needed to test specific cast logic without actual encryption.
 *    - Test the handling of `null` values.
 *    - Test the error handling for `DecryptException`.
 *
 * 💡 Logic:
 *    - Uses Laravel's service container (`app()`) internally to resolve the `Encrypter` and `LoggerInterface` instances, avoiding direct Facade usage.
 *    - Handles `null` values gracefully in both `get` and `set`.
 *    - Logs decryption failures but currently returns the encrypted value (consider changing this behavior).
 *
 * @package Ultra\UltraConfigManager\Casts
 */
class EncryptedCast implements CastsAttributes
{
    /**
     * 🔒 Decrypts the attribute's value when accessed on the model.
     * Handles null values and decryption errors.
     *
     * @param \Illuminate\Database\Eloquent\Model $model The Eloquent model instance.
     * @param string $key The attribute key being accessed.
     * @param mixed $value The raw value retrieved from the database (potentially encrypted).
     * @param array<string, mixed> $attributes All raw attributes retrieved from the database.
     * @return mixed The decrypted value, or null. Returns original value on decryption error (with logging).
     * @readOperation Decrypts data.
     * @log Logs decryption errors.
     */
    public function get($model, string $key, $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            // Resolve Encrypter from container instead of using Crypt facade
            $encrypter = $this->resolveEncrypter();
            return $encrypter->decryptString($value);
        } catch (DecryptException $e) {
            // Log the decryption failure
            $logger = $this->resolveLogger();
            $logger?->error('EncryptedCast: Failed to decrypt attribute.', [
                'key' => $key,
                'model' => get_class($model),
                'model_id' => $model->getKey(), // Get primary key if available
                'error' => $e->getMessage(),
                // Optionally include part of the value for debugging (BE CAREFUL WITH SENSITIVE DATA)
                // 'value_snippet' => substr($value, 0, 10) . '...'
            ]);

            // --- ORCD: Decision Point ---
            // Original behavior: Return the encrypted value. Hides errors like APP_KEY change.
            // return $value;

            // Alternative: Return null to indicate failure without breaking type hints?
            // return null;

            // Recommended: Throw a specific exception to clearly signal the failure.
            // throw new \RuntimeException("Failed to decrypt attribute '{$key}' for model " . get_class($model) . " [ID: {$model->getKey()}]. Check APP_KEY and data integrity.", 0, $e);
            // For now, let's stick to original behavior but ensure logging is present.
            return $value; // Returning original encrypted value
        } catch (Throwable $e) {
             // Catch other potential errors during encrypter resolution or decryption
             $logger = $this->resolveLogger();
             $logger?->error('EncryptedCast: Unexpected error during decryption.', [
                 'key' => $key,
                 'model' => get_class($model),
                 'model_id' => $model->getKey(),
                 'exception' => $e::class,
                 'error' => $e->getMessage(),
             ]);
             // Decide on return behavior for unexpected errors as well
             return $value; // Or throw
        }
    }

    /**
     * 🔒 Encrypts the attribute's value before saving it to the database.
     * Handles null values.
     *
     * @param \Illuminate\Database\Eloquent\Model $model The Eloquent model instance.
     * @param string $key The attribute key being set.
     * @param mixed $value The plain text value to be encrypted.
     * @param array<string, mixed> $attributes The current attributes being set on the model.
     * @return string|null The encrypted string representation of the value, or null.
     * @writeOperation Encrypts data.
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            // Resolve Encrypter from container
            $encrypter = $this->resolveEncrypter();
            return $encrypter->encryptString($value);
        } catch (Throwable $e) {
             // Log encryption errors
             $logger = $this->resolveLogger();
             $logger?->error('EncryptedCast: Failed to encrypt attribute.', [
                 'key' => $key,
                 'model' => get_class($model),
                 'model_id' => $model->getKey(),
                 'exception' => $e::class,
                 'error' => $e->getMessage(),
             ]);
             // Re-throw exception to prevent saving potentially unencrypted data silently
             throw new \RuntimeException("Failed to encrypt attribute '{$key}' for model " . get_class($model) . ".", 0, $e);
        }
    }

    /**
     * 🏭 Resolves the Encrypter contract instance from the service container.
     * @internal
     * @return Encrypter
     * @throws \RuntimeException if Encrypter cannot be resolved.
     */
    protected function resolveEncrypter(): Encrypter
    {
        if (function_exists('app') && app()->bound(Encrypter::class)) {
             try {
                 return app(Encrypter::class);
             } catch (Throwable $e) {
                 throw new \RuntimeException("Could not resolve Encrypter contract from container.", 0, $e);
             }
        }
        // This should ideally not be reached in a Laravel application context
        throw new \RuntimeException("Laravel application container or Encrypter contract not available.");
    }

    /**
     * 📝 Helper method to safely resolve a Logger instance.
     * @internal
     * @return LoggerInterface|null
     */
    protected function resolveLogger(): ?LoggerInterface
    {
        if (function_exists('app') && app()->bound(LoggerInterface::class)) {
            try {
                return app(LoggerInterface::class);
            } catch (Throwable $e) {
                error_log('EncryptedCast: Failed to resolve LoggerInterface: ' . $e->getMessage());
                return null;
            }
        }
        return null;
    }
}