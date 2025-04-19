<?php

/**
 * 📜 Oracode Model: UltraConfigModel
 *
 * @package         Ultra\UltraConfigManager\Models
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Psr\Log\LoggerInterface; // Per Dependency Injection (opzionale)
use Ultra\UltraConfigManager\Casts\EncryptedCast;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Database\Factories\UltraConfigModelFactory; // Path corretto per la Factory
// Import eccezioni se necessario (es. in boot)
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * 🎯 Purpose: Represents a single configuration entry within the UltraConfigManager system.
 *    This Eloquent model defines the structure, behavior, and relationships for configuration
 *    records stored in the database, including automatic value encryption, category management,
 *    versioning, auditing links, and key immutability.
 *
 * 🧱 Structure:
 *    - Eloquent Model extending `Illuminate\Database\Eloquent\Model`.
 *    - Traits: `SoftDeletes` for historical preservation, `HasFactory` for testing.
 *    - Properties: `id`, `key`, `value`, `category`, `note`, timestamps (`created_at`, `updated_at`, `deleted_at`).
 *    - Casts: `value` to `EncryptedCast`, `category` to `CategoryEnum`.
 *    - Relationships: `versions()` (HasMany), `audits()` (HasMany).
 *    - Mutators: `setKeyAttribute()` for key validation.
 *    - Boot Logic: Event listeners (`creating`, `saving`) for validation and key immutability.
 *
 * 🧩 Context: Used by `EloquentConfigDao` to interact with the `uconfig` database table.
 *    Represents the persisted state of a configuration item.
 *
 * 🛠️ Usage: Instantiated and managed primarily through `EloquentConfigDao`. Application code
 *    typically interacts with configuration via `UltraConfigManager` or the `UConfig` Facade,
 *    not directly with this model.
 *
 * 💾 State: Represents a row in the `uconfig` database table.
 *
 * 🗝️ Key Features:
 *    - `$table`: 'uconfig'.
 *    - `$fillable`: Defines mass-assignable attributes.
 *    - `$casts`: Handles automatic encryption/decryption of `value` and Enum casting for `category`.
 *    - `SoftDeletes`: Ensures records are marked deleted rather than removed.
 *    - Key Immutability: Prevents changing the `key` after creation via `boot()` logic.
 *    - Relationships: Links to related version and audit history.
 *
 * 🚦 Signals:
 *    - Throws `InvalidArgumentException` if `key` is invalid during set/create.
 *    - Throws `LogicException` if an attempt is made to modify the `key` after creation.
 *    - Eloquent events (`creating`, `created`, `saving`, `saved`, `updating`, `updated`, `deleting`, `deleted`, `restoring`, `restored`) are fired.
 *
 * 🛡️ Privacy (GDPR):
 *    - Crucial for GDPR compliance due to the `EncryptedCast` on the `value` attribute, ensuring configuration values are encrypted at rest in the database.
 *    - The `key`, `category`, `note` are stored in plain text – avoid storing PII in these fields.
 *    - The model itself doesn't store `userId`, but links to `UltraConfigAudit` which does.
 *    - `@privacy-internal`: Stores configuration `key`, `category`, `note` (plain text) and `value` (encrypted).
 *    - `@privacy-feature`: Automatic encryption at rest for the `value` field via `EncryptedCast`.
 *
 * 🤝 Dependencies:
 *    - `EncryptedCast`: For value encryption/decryption.
 *    - `CategoryEnum`: For category value mapping.
 *    - `UltraConfigVersion`, `UltraConfigAudit`: For relationships.
 *    - `UltraConfigModelFactory`: For testing.
 *    - `LoggerInterface` (Optional): If logging within boot events without Facades is desired.
 *    - (Implicit) Laravel Database connection and Eloquent subsystem.
 *
 * 🧪 Testing:
 *    - Use the associated Factory (`UltraConfigModelFactory`) to create instances in tests.
 *    - Verify CRUD operations via DAO tests.
 *    - Test the `EncryptedCast` separately or via integration tests verifying DB values are encrypted.
 *    - Test the `CategoryEnum` casting.
 *    - Test relationships (`versions`, `audits`).
 *    - Test key validation in `setKeyAttribute`.
 *    - Test key immutability logic in `boot()` method (ensure exception is thrown on update attempt).
 *    - Test soft delete and restoration.
 *
 * 💡 Logic:
 *    - Standard Eloquent model setup.
 *    - Key immutability enforced in the `saving` event listener.
 *    - Validation of key format in the `setKeyAttribute` mutator.
 *    - Automatic data handling via Eloquent casts is preferred over manual logic where possible.
 *
 * @property-read int $id
 * @property string $key Unique configuration key. Cannot be changed after creation.
 * @property mixed $value Configuration value (stored encrypted).
 * @property ?CategoryEnum $category Configuration category (cast to Enum).
 * @property ?string $note Optional descriptive note.
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at Soft delete timestamp.
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UltraConfigVersion> $versions Version history.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UltraConfigAudit> $audits Audit history.
 *
 * @package Ultra\UltraConfigManager\Models
 */
class UltraConfigModel extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * 💾 The database table used by the model.
     * @var string
     */
    protected $table = 'uconfig';

    /**
     * ✍️ The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'category',
        'note',
        'source_file',
    ];

    /**
     * 🎭 The attributes that should be cast to native types or custom classes.
     * Handles encryption for 'value' and Enum for 'category'.
     * @var array<string, string>
     */
    protected $casts = [
        'value' => EncryptedCast::class,
        'category' => CategoryEnum::class,
        'deleted_at' => 'datetime', // Explicit cast for soft delete timestamp
    ];

    /**
     * 🏭 Specifies the factory class name for the model.
     * @var string
     */
    protected static string $factory = UltraConfigModelFactory::class;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Ultra\UltraConfigManager\Database\Factories\UltraConfigModelFactory
     */
    protected static function newFactory(): UltraConfigModelFactory
    {
        return UltraConfigModelFactory::new();
    }

    /**
     * 🔗 Defines the relationship to the configuration's version history.
     * One configuration has many versions.
     *
     * @return HasMany<UltraConfigVersion>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(UltraConfigVersion::class, 'uconfig_id', 'id');
    }

    /**
     * 🔗 Defines the relationship to the configuration's audit history.
     * One configuration has many audit records.
     *
     * @return HasMany<UltraConfigAudit>
     */
    public function audits(): HasMany
    {
        return $this->hasMany(UltraConfigAudit::class, 'uconfig_id', 'id');
    }

    /**
     * 🛡️ Mutator for the 'category' attribute.
     * Ensures null values are stored as the backing value of CategoryEnum::None ('')
     * for semantic consistency when retrieving the model.
     *
     * @param ?string $value The proposed category value (string from Enum or null).
     * @return void
     */
    public function setCategoryAttribute(?string $value): void
    {
        // If the value is null, store the backing value of None ('').
        // Otherwise, store the provided string value (e.g., 'system', 'application').
        // The CategoryEnum cast will handle validation of non-null strings on retrieval/setting if needed.
        $this->attributes['category'] = $value ?? CategoryEnum::None->value;
    }

    /**
     * 🛡️ Mutator for the 'key' attribute.
     * Validates the key format before setting it.
     *
     * @param string $value The proposed key value.
     * @return void
     * @throws InvalidArgumentException If the key format is invalid.
     */
    public function setKeyAttribute(string $value): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $value)) {
            // Log the error if a logger is available (see boot method)
            static::getLogger()?->error('UCM Model: Invalid configuration key format attempted.', ['key' => $value]);
            throw new InvalidArgumentException("Configuration key must be alphanumeric with allowed characters: _ . -");
        }
        $this->attributes['key'] = $value;
    }

    /**
     * 🚀 Boots the model and registers event listeners for validation and immutability.
     * Handles logging via an optionally injected logger.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        $logger = static::getLogger(); // Get logger once

        // --- Event Listener: 'creating' ---
        static::creating(function (self $model) use ($logger) {
            // Validate key presence (already validated by mutator for format if set directly)
            if (empty($model->key)) {
                $logger?->error('UCM Model: Attempt to create configuration without a key.');
                throw new InvalidArgumentException('Configuration key cannot be empty during creation.');
            }
            $logger?->info('UCM Model: Creating configuration.', ['key' => $model->key]);
        });

        // --- Event Listener: 'updating' ---
        static::updating(function (self $model) use ($logger) {
            $logger?->info('UCM Model: Updating configuration.', ['key' => $model->key, 'id' => $model->id]);
        });

        // --- Event Listener: 'saving' ---
        // Enforces key immutability after creation.
        static::saving(function (self $model) use ($logger) {
            // Check if the model exists (i.e., not being created) AND the key attribute is dirty (changed)
            if ($model->exists && $model->isDirty('key')) {
                 $originalKey = $model->getOriginal('key');
                 $logger?->error('UCM Model: Attempted to modify immutable key after creation.', [
                     'id' => $model->id,
                     'original_key' => $originalKey,
                     'attempted_key' => $model->key,
                 ]);
                // Prevent the save operation by throwing an exception
                throw new LogicException("Configuration key ('{$originalKey}') cannot be modified after creation.");
            }
        });

         // --- Event Listener: 'deleted' --- (Example of logging delete)
         static::deleted(function (self $model) use ($logger) {
            $logger?->info('UCM Model: Configuration soft-deleted.', ['key' => $model->key, 'id' => $model->id]);
         });

         // --- Event Listener: 'restored' --- (Example of logging restore)
         static::restored(function (self $model) use ($logger) {
            $logger?->info('UCM Model: Configuration restored.', ['key' => $model->key, 'id' => $model->id]);
         });
    }

    /**
     * 📝 Helper method to safely get a Logger instance from the service container.
     * Allows logging within static boot methods without direct Facade dependency.
     * Returns null if the logger cannot be resolved (e.g., outside Laravel context).
     *
     * @return LoggerInterface|null
     * @internal
     */
    protected static function getLogger(): ?LoggerInterface
    {
        // Check if the 'app' function exists and the container has the logger bound
        if (function_exists('app') && app()->bound(LoggerInterface::class)) {
            try {
                return app(LoggerInterface::class);
            } catch (Throwable $e) {
                // Silently ignore if logger resolution fails
                error_log('UCM Model: Failed to resolve LoggerInterface: ' . $e->getMessage()); // Log to PHP error log as fallback
                return null;
            }
        }
        return null;
    }
}