<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Speaker extends Model
{
    protected $fillable = [
        'name',
        'headline',
        'bio',
        'photo_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('uploads/'.$this->photo_path) : null;
    }

    protected static function booted(): void
    {
        static::deleting(function (Speaker $speaker): void {
            if ($speaker->photo_path) {
                Storage::disk('uploads')->delete($speaker->photo_path);
            }
        });
    }
}
