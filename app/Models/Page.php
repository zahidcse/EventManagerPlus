<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'body',
        'hero_image_path',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Page $page): void {
            if ($page->hero_image_path) {
                Storage::disk('uploads')->delete($page->hero_image_path);
            }
        });
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isVisibleOnFrontend(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->published_at === null) {
            return true;
        }

        return $this->published_at->lte(now());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublishedOnFrontend(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}
