<?php

/**
 * Defines globally accessible constant values for UltraConfigManager.
 *
 * Provides a centralized source of truth for standard identifiers or default values
 * used throughout the UCM package, enhancing maintainability and readability.
 * Includes static helper methods for safe retrieval and validation.
 * This class cannot be instantiated.
 *
 * @package     Ultra\UltraConfigManager\Constants
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.1.1 // Updated documentation to Oracode v1.5.0 standard.
 * @since       1.0.0
 */

namespace Ultra\UltraConfigManager\Constants;

use InvalidArgumentException; // PHP Standard Exception
use ReflectionClass; // For constant introspection

/**
 * Provides global constants and validation methods for the UCM package.
 *
 * --- Key Constants ---
 * - `NO_USER`: Identifier for unknown/system user actions (e.g., in audits). Value: 0.
 * - `DEFAULT_CATEGORY`: Default category identifier (consider `CategoryEnum::None->value`). Value: 'general'.
 * --- End Key Constants ---
 *
 * --- Usage ---
 * Access constants directly: `GlobalConstants::NO_USER`
 * Validate constant existence: `GlobalConstants::validateConstant('NO_USER')`
 * Get constant value safely: `GlobalConstants::getConstant('NO_USER', -1)`
 * --- End Usage ---
 *
 * @internal This class uses Reflection API for its helper methods (`getConstant`, `validateConstant`).
 * @privacy-safe Constants defined here (NO_USER, DEFAULT_CATEGORY) are not PII.
 */
class GlobalConstants // Changed to final as it's not meant to be extended
{
    /**
     * Identifier for an unknown, anonymous, or system user.
     * Used in audit/version logs when a specific user context is unavailable.
     * @var int
     */
    public const NO_USER = null;

    /**
     * Default configuration category identifier.
     * Consider using `CategoryEnum::None->value` if a specific 'None' state is defined in the Enum.
     * @var string
     */
    public const DEFAULT_CATEGORY = 'general'; // Kept original value

    /**
     * Private constructor to prevent instantiation.
     * This class should only be used statically.
     * @codeCoverageIgnore Cannot be tested as it prevents instantiation.
     */
    private function __construct()
    {
        // Cannot be instantiated.
    }

    /**
     * Safely retrieves the value of a defined public constant by its name.
     *
     * Uses reflection to dynamically access constants, making it adaptable
     * to new constants added to this class. Returns a default value if
     * the constant name is not found.
     *
     * @param string $name The case-sensitive name of the public constant (e.g., 'NO_USER').
     * @param mixed $default The value to return if the constant is not defined. Defaults to null.
     *
     * @return mixed The value of the constant if found; otherwise, the `$default` value.
     * @static
     * @see \ReflectionClass::getConstants() Used for introspection.
     * @internal Logs reflection errors to PHP error log but returns default.
     */
    public static function getConstant(string $name, mixed $default = null): mixed
    {
        try {
            $reflection = new ReflectionClass(self::class);
            // Retrieve only public constants.
            $constants = $reflection->getConstants(\ReflectionClassConstant::IS_PUBLIC);

            return $constants[$name] ?? $default;

        } catch (\ReflectionException $e) {
             // Log internal error, return default as per contract.
             error_log("Reflection Error in GlobalConstants::getConstant for '{$name}': " . $e->getMessage());
             return $default;
        }
    }

    /**
     * Validates if a public constant with the given name exists in this class.
     *
     * Uses reflection to check for the constant's existence. Throws an
     * InvalidArgumentException if the constant name is not defined.
     *
     * @param string $name The case-sensitive name of the public constant to validate (e.g., 'NO_USER').
     * @return void
     *
     * @throws InvalidArgumentException If the constant `$name` is not defined.
     * @throws \RuntimeException If reflection itself fails unexpectedly.
     * @static
     * @see \ReflectionClass::getConstants() Used for introspection.
     */
    public static function validateConstant(string $name): void
    {
        try {
            $reflection = new ReflectionClass(self::class);
            // Retrieve only public constants.
            $constants = $reflection->getConstants(\ReflectionClassConstant::IS_PUBLIC);

            // Check if the key exists in the retrieved public constants.
            if (!array_key_exists($name, $constants)) {
                $valid = implode(', ', array_keys($constants));
                // Provide a clear error message listing valid options.
                throw new InvalidArgumentException(
                    "Constant '{$name}' does not exist in " . self::class . ". Valid public constants are: [{$valid}]"
                );
            }
            // If key exists, validation passes silently (returns void).

        } catch (\ReflectionException $e) {
             // Wrap reflection error in a standard runtime exception for consistency.
             throw new \RuntimeException("Error validating constants via reflection: " . $e->getMessage(), 0, $e);
        }
    }
}