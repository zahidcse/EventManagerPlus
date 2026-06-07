<?php

namespace App\Models;

use App\Support\BookingDayCart;
use App\Support\PublicBookingPayload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EventBooking extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'event_ticket_id',
        'seat_id',
        'order_group_id',
        'occurrence_date',
        'attendee_name',
        'email',
        'phone',
        'attendee_meta',
        'status',
        'notes',
        'stripe_checkout_session_id',
        'paypal_order_id',
        'razorpay_payment_id',
        'sslcommerz_val_id',
        'offline_payment_method',
        'offline_payment_reference',
        'check_in_token',
        'checked_in_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'check_in_token',
    ];

    protected function casts(): array
    {
        return [
            'occurrence_date' => 'date',
            'attendee_meta' => 'array',
            'checked_in_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EventBooking $booking): void {
            if ($booking->check_in_token === null || $booking->check_in_token === '') {
                $booking->check_in_token = Str::lower(Str::random(48));
            }
            if ($booking->order_group_id === null || trim((string) $booking->order_group_id) === '') {
                $booking->order_group_id = 'ord-'.Str::lower(Str::random(24));
            }
        });

        static::created(function (EventBooking $booking): void {
            static::refreshRegistrationCount((int) $booking->event_id);
        });

        static::deleted(function (EventBooking $booking): void {
            static::refreshRegistrationCount((int) $booking->event_id);
        });
    }

    private static function refreshRegistrationCount(int $eventId): void
    {
        if ($eventId <= 0) {
            return;
        }

        if (! Event::query()->whereKey($eventId)->exists()) {
            return;
        }

        $count = static::query()->where('event_id', $eventId)->count();
        Event::query()->whereKey($eventId)->update(['registrations_count' => $count]);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(EventTicket::class, 'event_ticket_id');
    }

    public function seatDisplayLabel(): ?string
    {
        return null;
    }

    public function adminNotificationDismissals(): HasMany
    {
        return $this->hasMany(AdminBookingNotificationDismissal::class, 'event_booking_id');
    }

    public function markCheckedIn(): bool
    {
        if ($this->checked_in_at !== null) {
            return false;
        }

        $this->forceFill([
            'checked_in_at' => now(),
            'status' => 'checked_in',
        ])->save();

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $attributes
     * @return Collection<int, EventBooking>
     */
    public static function createManyFromCartPayload(Event $event, array $payload, array $attributes): Collection
    {
        $extraNotes = isset($attributes['notes']) ? trim((string) $attributes['notes']) : '';
        unset($attributes['notes']);
        $attendeeEntries = self::normalizedAttendeeEntriesFromPayload($payload);
        $attendeeIndex = 0;

        $created = collect();
        foreach (BookingDayCart::activeDayCartsForPayload($event, $payload) as $dayCart) {
            $occDate = $dayCart['date'];
            $dayNotes = PublicBookingPayload::notesForDayCart($event, $dayCart);
            $rowNotes = self::mergeBookingNotes($dayNotes, $extraNotes);
            $dayOrderGroupId = 'ord-'.Str::lower(Str::random(24));

            foreach ($event->tickets as $ticket) {
                $qty = (int) ($dayCart['qty'][(string) $ticket->id] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                for ($i = 0; $i < $qty; $i++) {
                    $perSeatAttributes = $attributes;
                    if (isset($attendeeEntries[$attendeeIndex])) {
                        $perSeatAttributes = array_merge(
                            $perSeatAttributes,
                            self::bookingColumnsFromAttendeeEntry($attendeeEntries[$attendeeIndex])
                        );
                    }
                    $attendeeIndex++;

                    $created->push(static::query()->create(array_merge($perSeatAttributes, [
                        'event_id' => $event->id,
                        'event_ticket_id' => $ticket->id,
                        'order_group_id' => $dayOrderGroupId,
                        'occurrence_date' => $occDate,
                        'notes' => $rowNotes !== '' ? $rowNotes : null,
                    ])));
                }
            }
        }

        return $created;
    }

    private static function mergeBookingNotes(?string $dayPart, string $suffix): string
    {
        $dayPart = trim((string) $dayPart);
        $suffix = trim($suffix);
        if ($dayPart === '') {
            return $suffix;
        }
        if ($suffix === '') {
            return $dayPart;
        }

        return $dayPart."\n\n".$suffix;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, string>>
     */
    private static function normalizedAttendeeEntriesFromPayload(array $payload): array
    {
        $raw = $payload['attendee_entries'] ?? null;
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $entry = [];
            foreach ($row as $k => $v) {
                if (! is_string($k)) {
                    continue;
                }
                $value = trim((string) $v);
                if ($value !== '') {
                    $entry[$k] = $value;
                }
            }
            if ($entry !== []) {
                $out[] = $entry;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $entry
     * @return array<string, mixed>
     */
    private static function bookingColumnsFromAttendeeEntry(array $entry): array
    {
        $columns = [];
        if (($entry['name'] ?? '') !== '') {
            $columns['attendee_name'] = $entry['name'];
        }
        if (($entry['email'] ?? '') !== '') {
            $columns['email'] = $entry['email'];
        }
        if (($entry['phone'] ?? '') !== '') {
            $columns['phone'] = $entry['phone'];
        }

        $meta = [];
        foreach ($entry as $key => $value) {
            if (in_array($key, ['name', 'email', 'phone'], true)) {
                continue;
            }
            $meta[$key] = $value;
        }
        if ($meta !== []) {
            $columns['attendee_meta'] = $meta;
        }

        return $columns;
    }
}
