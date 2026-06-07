<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Admin\AdminModules;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'timezone',
        'password',
        'is_admin',
        'is_organizer',
        'organizer_id',
        'admin_role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_organizer' => 'boolean',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->adminRole?->is_super === true;
    }

    public function canAccessAdminPanel(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if (! $this->is_organizer || $this->organizer_id === null) {
            return false;
        }

        $organizer = $this->relationLoaded('organizer')
            ? $this->organizer
            : $this->organizer()->first();

        return $organizer !== null
            && $organizer->status === 'active'
            && $this->admin_role_id !== null
            && $this->adminRole !== null;
    }

    public function canAccessAdminModule(string $module): bool
    {
        if (! $this->canAccessAdminPanel()) {
            return false;
        }

        if ($this->is_admin && ! $this->is_organizer) {
            if ($this->adminRole === null) {
                return true;
            }

            return $this->adminRole->hasModule($module);
        }

        if ($this->is_organizer) {
            if ($module === AdminModules::STAFF) {
                return false;
            }

            return $this->adminRole?->hasModule($module) ?? false;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function adminModulePermissions(): array
    {
        if (! $this->canAccessAdminPanel()) {
            return [];
        }

        if ($this->is_admin && ! $this->is_organizer) {
            if ($this->adminRole === null) {
                return AdminModules::keys();
            }

            return $this->adminRole->permissionKeys();
        }

        return array_values(array_filter(
            $this->adminRole?->permissionKeys() ?? [],
            fn (string $module): bool => $module !== AdminModules::STAFF,
        ));
    }
}
