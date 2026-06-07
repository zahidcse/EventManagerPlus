<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminBookingNotificationDismissal extends Model
{
    protected $fillable = [
        'user_id',
        'event_booking_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(EventBooking::class, 'event_booking_id');
    }
}
