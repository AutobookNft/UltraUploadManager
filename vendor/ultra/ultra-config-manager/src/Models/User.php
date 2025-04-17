<?php

/**
 * 📜 Oracode Model: User
 *
 * @package         Ultra\UltraConfigManager\Models
 * @version         1.1.0 // Version bump for boot refactoring (remove UltraLog Facade)
 * @author          Fabio Cherici / Padmin D. Curtis (Refactoring)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Models;

// Rimuovi: use Ultra\UltraLogManager\Facades\UltraLog;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use InvalidArgumentException; // Usa per validazione

/**
 * User - Represents an authenticated user in the UltraConfigManager system.
 *
 * This model defines users who interact with configurations, providing authentication,
 * role-based permissions via Spatie, and audit tracking integration. It serves as
 * the default user model for UltraConfigManager's audit and versioning features.
 * Refactored to remove direct logging dependencies from boot method.
 *
 * @property int $id The unique identifier of the user.
 * @property string $name The user's display name.
 * @property string $email The user's email address (unique).
 * @property string $password The hashed password for authentication.
 * @property string|null $remember_token Token for "remember me" functionality.
 * @property \Illuminate\Support\Carbon|null $created_at Creation timestamp.
 * @property \Illuminate\Support\Carbon|null $updated_at Last update timestamp.
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UltraConfigAudit> $audits
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UltraConfigVersion> $versions
 */
class User extends Authenticatable
{
    use Notifiable, HasRoles; // HasRoles incluso anche se non usato direttamente qui, per compatibilità Spatie

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Aggiunto per Laravel 10+ standard
    ];

    /**
     * Get the audit entries created by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Ultra\UltraConfigManager\Models\UltraConfigAudit>
     */
    public function audits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UltraConfigAudit::class, 'user_id');
    }

    /**
     * Get the configuration versions created by this user.
     * (Note: user_id is not standard on versions table based on stub)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Ultra\UltraConfigManager\Models\UltraConfigVersion>
     */
    public function versions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UltraConfigVersion::class, 'user_id');
    }

    /**
     * Boot the model and add validation protections.
     * Logging removed to decouple from specific logging implementation.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            // Validation only
            if (empty($model->name) || empty($model->email)) {
                // Logging removed
                throw new InvalidArgumentException('User must have a name and email');
            }
            if (!filter_var($model->email, FILTER_VALIDATE_EMAIL)) {
                // Logging removed
                throw new InvalidArgumentException('User email must be a valid email address');
            }
            // Logging removed: UltraLog::info('UCM Action', "Creating user: {$model->email}");
        });

        static::updating(function ($model) {
            // Validation only
            if ($model->isDirty('email') && !filter_var($model->email, FILTER_VALIDATE_EMAIL)) {
                // Logging removed
                throw new InvalidArgumentException('User email must remain a valid email address');
            }
            // Logging removed: UltraLog::info('UCM Action', "Updating user: {$model->email}");
        });
    }
}