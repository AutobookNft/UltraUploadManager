<?php

/**
 * 📜 Oracode Model: UltraConfigAudit
 *
 * @package         Ultra\UltraConfigManager\Models
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Psr\Log\LoggerInterface; // Per Dependency Injection (opzionale)
use Ultra\UltraConfigManager\Casts\EncryptedCast;
// Import Models used in relationships
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\User; // Assuming User model exists here or adjust namespace
// Import exceptions
use InvalidArgumentException;
use Throwable;

/**
 * 🎯 Purpose: Represents an audit log entry recording a specific change (create, update, delete, restore)
 *    to a configuration entry within the UltraConfigManager system. Ensures traceability of modifications,
 *    linking the change to a user and storing the state before and after the action (encrypted).
 *
 * 🧱 Structure:
 *    - Eloquent Model extending `Illuminate\Database\Eloquent\Model`.
 *    - Properties: `id`, `uconfig_id`, `action`, `old_value`, `new_value`, `user_id`, `created_at`, `updated_at`.
 *    - Casts: `old_value` and `new_value` to `EncryptedCast`.
 *    - Relationships: `uconfig()` (BelongsTo), `user()` (BelongsTo).
 *    - Boot Logic: Event listener (`creating`) for validation of the `action` field.
 *
 * 🧩 Context: Created by `EloquentConfigDao` (typically within the `saveConfig` or `deleteConfigByKey`
 *    transaction) whenever a configuration change occurs and auditing is enabled. Represents a row
 *    in the `uconfig_audit` table.
 *
 * 🛠️ Usage: Primarily used for reviewing the history of changes to configurations. Accessed via the
 *    `audits` relationship on `UltraConfigModel` or directly through `EloquentConfigDao`.
 *
 * 💾 State: Represents a row in the `uconfig_audit` database table.
 *
 * 🗝️ Key Features:
 *    - `$table`: 'uconfig_audit'.
 *    - `$fillable`: Defines mass-assignable attributes.
 *    - `$casts`: Ensures `old_value` and `new_value` are encrypted/decrypted.
 *    - Relationships: Links to the specific `UltraConfigModel` that was changed and the `User` who performed the action.
 *    - Validation: Ensures the `action` field contains a valid, recognized value upon creation.
 *
 * 🚦 Signals:
 *    - Throws `InvalidArgumentException` during creation if validation (`action`) fails.
 *    - Eloquent events (`creating`, `created`, etc.) are fired.
 *
 * 🛡️ Privacy (GDPR):
 *    - Stores historical configuration values (`old_value`, `new_value`) encrypted at rest via `EncryptedCast`.
 *    - Stores `user_id`, which links the audit record to a specific user (potentially PII depending on context). Access control to audit logs is important.
 *    - Stores `action` (non-sensitive).
 *    - `@privacy-internal`: Stores historical config values (encrypted), action (plain text), `user_id`.
 *    - `@privacy-feature`: Automatic encryption at rest for `old_value` and `new_value` fields via `EncryptedCast`.
 *    - `@privacy-consideration`: Audit logs provide a history of changes, including who made them. Ensure appropriate access controls and retention policies are in place.
 *
 * 🤝 Dependencies:
 *    - `EncryptedCast`.
 *    - `UltraConfigModel`, `User` (for relationships).
 *    - `LoggerInterface` (Optional, for boot logic logging).
 *    - (Implicit) Laravel Database connection and Eloquent subsystem.
 *
 * 🧪 Testing:
 *    - Verify record creation with correct attributes (including encrypted values) via DAO tests.
 *    - Test relationships (`uconfig`, `user`).
 *    - Test casts (`EncryptedCast`).
 *    - Test validation in `boot()` method (ensure exception is thrown on invalid action).
 *
 * 💡 Logic:
 *    - Data model representing an audit trail event.
 *    - Core logic resides in the casts and boot validation for the `action` field.
 *
 * @property-read int $id
 * @property int $uconfig_id Foreign key linking to the `uconfig` table.
 * @property string $action The action performed ('created', 'updated', 'deleted', 'restored').
 * @property mixed|null $old_value The configuration value before the change (stored encrypted). Null for 'created' actions.
 * @property mixed|null $new_value The configuration value after the change (stored encrypted). Null for 'deleted' actions.
 * @property ?int $user_id ID of the user who performed the action. Null if action was automated or user unknown.
 * @property ?\Illuminate\Support\Carbon $created_at Timestamp of audit record creation.
 * @property ?\Illuminate\Support\Carbon $updated_at Timestamp of last update (usually same as created_at).
 *
 * @property-read UltraConfigModel $uconfig The configuration entry that was changed.
 * @property-read ?User $user The user who performed the action.
 *
 * @package Ultra\UltraConfigManager\Models
 */
class UltraConfigAudit extends Model
{
    /**
     * 💾 The database table used by the model.
     * @var string
     */
    protected $table = 'uconfig_audit';

    /**
     * ✍️ The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'uconfig_id',
        'action',
        'old_value',
        'new_value',
        'user_id',
    ];

    /**
     * 🎭 The attributes that should be cast to native types or custom classes.
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'old_value' => EncryptedCast::class, // Encrypt previous value
        'new_value' => EncryptedCast::class, // Encrypt new value
        'uconfig_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * ⏱️ Indicates if the model should be timestamped.
     * @var bool
     */
    public $timestamps = true;

    /**
     * 🔗 Defines the relationship back to the configuration entry that was changed.
     * An audit record belongs to one configuration.
     *
     * @return BelongsTo<UltraConfigModel, UltraConfigAudit>
     */
    public function uconfig(): BelongsTo
    {
        return $this->belongsTo(UltraConfigModel::class, 'uconfig_id');
    }

    /**
     * 🔗 Defines the relationship to the user who performed the audited action.
     * An audit record belongs to one user (or is anonymous).
     *
     * @return BelongsTo<User, UltraConfigAudit>
     */
    public function user(): BelongsTo
    {
        // Use the correct User model namespace
        // withDefault handles cases where user_id is null or the user is deleted
        return $this->belongsTo(User::class, 'user_id')->withDefault([
            'name' => 'Unknown/System', // Indicate system/unknown user clearly
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

        // --- Valid Action Values ---
        $validActions = ['created', 'updated', 'deleted', 'restored'];

        // --- Event Listener: 'creating' ---
        static::creating(function (self $model) use ($logger, $validActions) {
            // Validate the 'action' field
            if (empty($model->action) || !in_array($model->action, $validActions)) {
                $logger?->error('UCM Audit Model: Invalid action value for creation.', [
                    'uconfig_id' => $model->uconfig_id,
                    'action' => $model->action,
                    'valid_actions' => $validActions,
                ]);
                throw new InvalidArgumentException("Audit action must be one of: " . implode(', ', $validActions));
            }

            // Log the creation attempt
            $logger?->info('UCM Audit Model: Creating audit entry.', [
                'uconfig_id' => $model->uconfig_id,
                'action' => $model->action,
                'user_id' => $model->user_id,
            ]);
        });

         // --- Event Listener: 'created' --- (Example log after save)
         static::created(function (self $model) use ($logger) {
             $logger?->info('UCM Audit Model: Audit entry created successfully.', ['id' => $model->id, 'uconfig_id' => $model->uconfig_id, 'action' => $model->action]);
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
                 error_log('UCM Audit Model: Failed to resolve LoggerInterface: ' . $e->getMessage());
                return null;
            }
        }
        return null;
    }
}