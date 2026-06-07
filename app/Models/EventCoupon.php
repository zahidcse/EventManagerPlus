<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCoupon extends Model
{
    protected $fillable = [
        'event_id',
        'sort_order',
        'code',
        'discount_type',
        'discount_value',
        'max_uses',
        'uses_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'discount_value' => 'decimal:2',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
