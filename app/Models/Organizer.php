<?php

namespace App\Models;

use App\Support\PersonName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizer extends Model
{
    protected $fillable = [
        'name',
        'job_title',
        'company_name',
        'email',
        'phone',
        'password',
        'bio',
        'photo_path',
        'country',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'status',
        'events_count',
        'auto_approve_events',
        'digest_notifications',
        'admin_role_id',
        'user_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'auto_approve_events' => 'boolean',
            'digest_notifications' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'events_count' => 'integer',
        ];
    }

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasPanelAccess(): bool
    {
        return $this->user_id !== null && $this->admin_role_id !== null;
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function formattedName(): string
    {
        return PersonName::format($this->name);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->formattedName()), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 0) {
            return '?';
        }

        if (count($parts) === 1) {
            return strtoupper(mb_substr($parts[0], 0, 2));
        }

        return strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1));
    }
}
