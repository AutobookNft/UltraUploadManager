<?php

/**
 * 📜 Oracode Model: UltraConfigVersion
 *
 * @package         Ultra\UltraConfigManager\Models
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Models;

use Ultra\UltraConfigManager\Database\Factories\UltraConfigVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Psr\Log\LoggerInterface; // Per Dependency Injection (opzionale)
use Ultra\UltraConfigManager\Casts\EncryptedCast;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
// Import Models used in relationships
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\User; // Assuming User model exists here or adjust namespace
// Import exceptions
use InvalidArgumentException;
use Throwable;

/**
 * 🎯 Purpose: Represents a historical snapshot (version) of a configuration entry
 *    within the UltraConfigManager system. Stores the state of a configuration item
 *    (key, value, category, note) at a specific point in time, identified by a version number.
 *
 * 🧱 Structure:
 *    - Eloquent Model extending `Illuminate\Database\Eloquent\Model`.
 *    - Properties: `id`, `uconfig_id`, `version`, `key`, `category`, `note`, `value`, `created_at`, `updated_at`. Potentially `user_id`.
 *    - Casts: `value` to `EncryptedCast`, `category` to `CategoryEnum`, `version` to `integer`.
 *    - Relationships: `uconfig()` (BelongsTo), `user()` (BelongsTo, optional based on schema).
 *    - Boot Logic: Event listener (`creating`) for validation.
 *
 * 🧩 Context: Created by `EloquentConfigDao` (typically within the `saveConfig` transaction)
 *    whenever a configuration is created or updated and versioning is enabled. Represents a row
 *    in the `uconfig_versions` table.
 *
 * 🛠️ Usage: Primarily used for historical lookup and potential rollback scenarios (logic for
 *    rollback would reside in `UltraConfigManager` or a dedicated service, using data from this model).
 *    Accessed via the `versions` relationship on `UltraConfigModel` or directly through `EloquentConfigDao`.
 *
 * 💾 State: Represents a row in the `uconfig_versions` database table.
 *
 * 🗝️ Key Features:
 *    - `$table`: 'uconfig_versions'.
 *    - `$fillable`: Defines mass-assignable attributes.
 *    - `$casts`: Ensures `value` is encrypted/decrypted and `category` uses the Enum.
 *    - Relationships: Links back to the parent `UltraConfigModel` and potentially the `User` who triggered the change.
 *    - Validation: Ensures essential fields (`uconfig_id`, `version`, `key`) are present on creation.
 *
 * 🚦 Signals:
 *    - Throws `InvalidArgumentException` during creation if validation fails.
 *    - Eloquent events (`creating`, `created`, etc.) are fired.
 *
 * 🛡️ Privacy (GDPR):
 *    - Stores historical configuration values (`value`) encrypted at rest via `EncryptedCast`.
 *    - Stores `key`, `category`, `note` in plain text – avoid PII here.
 *    - May store `user_id` if the schema includes it, linking the version to a user action.
 *    - `@privacy-internal`: Stores historical config values (encrypted), key, category, note, potentially `userId`.
 *    - `@privacy-feature`: Automatic encryption at rest for the `value` field via `EncryptedCast`.
 *    - `@privacy-consideration`: Historical data remains even after the main config is deleted (due to soft delete on parent). Consider retention policies.
 *
 * 🤝 Dependencies:
 *    - `EncryptedCast`, `CategoryEnum`.
 *    - `UltraConfigModel`, `User` (for relationships).
 *    - `LoggerInterface` (Optional, for boot logic logging).
 *    - (Implicit) Laravel Database connection and Eloquent subsystem.
 *
 * 🧪 Testing:
 *    - Verify record creation with correct attributes (including encrypted value) via DAO tests.
 *    - Test relationships (`uconfig`, `user`).
 *    - Test casts (`EncryptedCast`, `CategoryEnum`).
 *    - Test validation in `boot()` method (ensure exception is thrown on invalid data).
 *
 * 💡 Logic:
 *    - Relatively simple data model representing a snapshot.
 *    - Core logic resides in the casts and boot validation.
 *
 * @property-read int $id
 * @property int $uconfig_id Foreign key linking to the `uconfig` table.
 * @property int $version The sequential version number for this config entry.
 * @property string $key The configuration key at the time of this version.
 * @property ?CategoryEnum $category The configuration category at the time of this version.
 * @property ?string $note The note associated with this version.
 * @property mixed $value The configuration value at the time of this version (stored encrypted).
 * @property ?int $user_id Optional: ID of the user who triggered this version creation.
 * @property ?\Illuminate\Support\Carbon $created_at Timestamp of version creation.
 * @property ?\Illuminate\Support\Carbon $updated_at Timestamp of last update (usually same as created_at).
 *
 * @property-read UltraConfigModel $uconfig The parent configuration entry.
 * @property-read ?User $user The user associated with this version (if tracked).
 *
 * @package Ultra\UltraConfigManager\Models
 */
class UltraConfigVersion extends Model
{
    // Note: No SoftDeletes needed for versions themselves typically.

    use HasFactory;

    /**
     * 💾 The database table used by the model.
     * @var string
     */
    protected $table = 'uconfig_versions';

    /**
     * ✍️ The attributes that are mass assignable.
     * Remember to add 'user_id' if your versions table includes it.
     * @var array<int, string>
     */
    protected $fillable = [
        'uconfig_id',
        'version',
        'key',
        'category',
        'note',
        'value',
        'user_id', 
    ];

    /**
     * 🎭 The attributes that should be cast to native types or custom classes.
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'value' => EncryptedCast::class,    // Encrypt the historical value
        'category' => CategoryEnum::class, // Cast category string to Enum
        'version' => 'integer',           // Cast version number to integer
        'uconfig_id' => 'integer',        // Cast foreign key to integer
        'user_id' => 'integer',          // Cast user_id to integer (if applicable)
    ];

    /**
     * ⏱️ Indicates if the model should be timestamped.
     * Typically true for versions to know when they were created.
     * @var bool
     */
    public $timestamps = true; // Default is true, can be set explicitly

    /**
     * Create a new factory instance for the model.
     *
     * @return \Ultra\UltraConfigManager\Database\Factories\UltraConfigVersionFactory
     */
    protected static function newFactory(): UltraConfigVersionFactory
    {
        return UltraConfigVersionFactory::new();
    }

    /**
     * 🔗 Defines the relationship back to the parent configuration entry.
     * A version belongs to one configuration.
     *
     * @return BelongsTo<UltraConfigModel, UltraConfigVersion>
     */
    public function uconfig(): BelongsTo
    {
        return $this->belongsTo(UltraConfigModel::class, 'uconfig_id');
    }

    /**
     * 🔗 Defines the relationship to the user who triggered this version (optional).
     * Assumes a `user_id` column exists on the `uconfig_versions` table.
     *
     * @return BelongsTo<User, UltraConfigVersion>
     */
    public function user(): BelongsTo
    {
        // Use the correct User model namespace
        // Add withDefault if user_id can be null or the user might be deleted
        return $this->belongsTo(User::class, 'user_id')->withDefault([
             // Provide default attributes for a non-existent user
            'name' => 'Unknown/System',
        ]);
    }

    /**
     * 🚀 Boots the model and registers event listeners for validation.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        $logger = static::getLogger();

        // --- Event Listener: 'creating' ---
        static::creating(function (self $model) use ($logger) {
            // Validate required fields before creation
            if (empty($model->uconfig_id) || !isset($model->version) || $model->version < 1) {
                $logger?->error('UCM Version Model: Invalid data for creation.', [
                    'uconfig_id' => $model->uconfig_id,
                    'version' => $model->version,
                ]);
                throw new InvalidArgumentException('Version entry requires a valid uconfig_id and version >= 1.');
            }
            if (empty($model->key)) {
                $logger?->error('UCM Version Model: Attempt to create version without a key.', ['uconfig_id' => $model->uconfig_id]);
                throw new InvalidArgumentException('Version key cannot be empty during creation.');
            }

            // Log the creation attempt (consider logging level based on verbosity needs)
            $logger?->info('UCM Version Model: Creating version entry.', [
                'uconfig_id' => $model->uconfig_id,
                'version' => $model->version,
                'key' => $model->key,
            ]);
        });

         // --- Event Listener: 'created' --- (Example log after save)
         static::created(function (self $model) use ($logger) {
             $logger?->info('UCM Version Model: Version entry created successfully.', ['id' => $model->id, 'uconfig_id' => $model->uconfig_id, 'version' => $model->version]);
         });
    }

    /**
     * 📝 Helper method to safely get a Logger instance from the service container.
     * @internal
     * @return LoggerInterface|null
     */
    protected static function getLogger(): ?LoggerInterface
    {
        if (function_exists('app') && app()->bound(LoggerInterface::class)) {
            try {
                return app(LoggerInterface::class);
            } catch (Throwable $e) {
                 error_log('UCM Version Model: Failed to resolve LoggerInterface: ' . $e->getMessage());
                return null;
            }
        }
        return null;
    }
}