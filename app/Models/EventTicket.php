<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTicket extends Model
{
    protected $table = 'event_tickets';

    protected $fillable = [
        'event_id',
        'sort_order',
        'name',
        'price',
        'early_bird_price',
        'early_bird_ends_at',
        'quantity',
        'sales_start',
        'sales_end',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'early_bird_price' => 'decimal:2',
            'quantity' => 'integer',
            'sort_order' => 'integer',
            'sales_start' => 'date',
            'sales_end' => 'date',
            'early_bird_ends_at' => 'date',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class, 'event_ticket_id');
    }

    public function effectiveUnitPrice(): float
    {
        $base = (float) $this->price;
        $eb = $this->early_bird_price;
        if ($eb !== null && (float) $eb >= 0) {
            if (! $this->early_bird_ends_at || now()->startOfDay()->lte($this->early_bird_ends_at)) {
                return (float) $eb;
            }
        }

        return $base;
    }

    public function bookingsCount(?string $occurrenceDate = null): int
    {
        $query = $this->bookings();
        $event = $this->relationLoaded('event') ? $this->getRelation('event') : $this->event;
        if ($event instanceof Event && $event->usesPerDayInventory() && is_string($occurrenceDate) && trim($occurrenceDate) !== '') {
            $query->whereDate('occurrence_date', $occurrenceDate);
        }

        return (int) $query->count();
    }

    public function hasUnlimitedQuantity(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * @return int|null Remaining bookable seats for this tier, or null when unlimited.
     */
    public function remainingForSale(?string $occurrenceDate = null): ?int
    {
        $event = $this->relationLoaded('event') ? $this->getRelation('event') : $this->event;

        if ($event instanceof Event && $event->usesGlobalTicketQuantity()) {
            return $event->remainingGlobalTicketPool($occurrenceDate);
        }

        if ($this->hasUnlimitedQuantity()) {
            return null;
        }

        return max(0, $this->quantity - $this->bookingsCount($occurrenceDate));
    }

    public function isWithinSalesWindow(): bool
    {
        $today = now()->startOfDay();
        if ($this->sales_start && $today->lt($this->sales_start->startOfDay())) {
            return false;
        }
        if ($this->sales_end && $today->gt($this->sales_end->endOfDay())) {
            return false;
        }

        return true;
    }

    public function isBookableNow(?string $occurrenceDate = null): bool
    {
        if (! $this->isWithinSalesWindow()) {
            return false;
        }
        $remaining = $this->remainingForSale($occurrenceDate);

        return $remaining === null || $remaining > 0;
    }
}
