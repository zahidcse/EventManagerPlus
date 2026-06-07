<?php

namespace App\Models;

use App\Support\Admin\AdminModules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    public const AUDIENCE_STAFF = 'staff';

    public const AUDIENCE_ORGANIZER = 'organizer';

    public const AUDIENCE_BOTH = 'both';

    protected $fillable = [
        'name',
        'slug',
        'audience',
        'description',
        'permissions',
        'is_super',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_super' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'admin_role_id');
    }

    public function organizers(): HasMany
    {
        return $this->hasMany(Organizer::class, 'admin_role_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForStaff(Builder $query): Builder
    {
        return $query
            ->where('is_super', false)
            ->whereIn('audience', [self::AUDIENCE_STAFF, self::AUDIENCE_BOTH]);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForOrganizer(Builder $query): Builder
    {
        return $query
            ->where('is_super', false)
            ->whereIn('audience', [self::AUDIENCE_ORGANIZER, self::AUDIENCE_BOTH]);
    }

    public function appliesToStaff(): bool
    {
        return $this->is_super || in_array($this->audience, [self::AUDIENCE_STAFF, self::AUDIENCE_BOTH], true);
    }

    public function appliesToOrganizer(): bool
    {
        return ! $this->is_super && in_array($this->audience, [self::AUDIENCE_ORGANIZER, self::AUDIENCE_BOTH], true);
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            self::AUDIENCE_STAFF => 'Staff only',
            self::AUDIENCE_ORGANIZER => 'Organizers only',
            default => 'Staff & organizers',
        };
    }

    public function hasModule(string $module): bool
    {
        if ($this->is_super) {
            return true;
        }

        $permissions = $this->permissions ?? [];

        return in_array($module, $permissions, true);
    }

    /**
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        if ($this->is_super) {
            return AdminModules::keys();
        }

        return array_values(array_intersect(
            AdminModules::keys(),
            $this->permissions ?? [],
        ));
    }
}
