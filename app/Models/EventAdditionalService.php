<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAdditionalService extends Model
{
    protected $table = 'event_additional_services';

    protected $fillable = [
        'event_id',
        'sort_order',
        'name',
        'price',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sort_order' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function hasUnlimitedQuantity(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * @return int|null Remaining units available, or null when unlimited.
     */
    public function remainingForSale(): ?int
    {
        if ($this->hasUnlimitedQuantity()) {
            return null;
        }

        return max(0, $this->quantity);
    }
}
