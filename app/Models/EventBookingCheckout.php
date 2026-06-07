<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBookingCheckout extends Model
{
    protected $fillable = [
        'event_id',
        'stripe_checkout_session_id',
        'paypal_order_id',
        'razorpay_order_id',
        'sslcommerz_tran_id',
        'status',
        'amount_total_cents',
        'currency',
        'payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'amount_total_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
