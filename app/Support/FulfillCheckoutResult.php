<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Support\Collection;

final class FulfillCheckoutResult
{
    /**
     * @param  Collection<int, EventBooking>  $bookings
     */
    public function __construct(
        public readonly bool $fulfilled,
        public readonly Collection $bookings,
        public readonly ?Event $event = null,
    ) {}
}
