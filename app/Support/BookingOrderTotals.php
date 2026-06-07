<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class BookingOrderTotals
{
    /**
     * @param  list<array{price_data: array{currency: string, product_data: array{name: string}, unit_amount: int}, quantity: int}>  $stripeLineItems
     */
    public function __construct(
        public readonly int $payableTotalCents,
        public readonly string $currency,
        public readonly array $stripeLineItems,
    ) {}

    public static function fromEventAndRequest(Event $event, Request $request): self
    {
        $currency = strtolower((string) config('services.stripe.currency', 'usd'));
        $stripeLineItems = [];
        $payableTotal = 0;

        $carts = BookingDayCart::dayCartsFromRequest($event, $request);

        foreach ($carts as $cart) {
            $daySuffix = '';
            $d = $cart['date'] ?? null;
            if (is_string($d) && $d !== '') {
                try {
                    $daySuffix = ' · '.Carbon::parse($d)->format('M j, Y');
                } catch (\Throwable) {
                    $daySuffix = ' · '.$d;
                }
            }

            foreach ($event->tickets as $ticket) {
                $q = (int) ($cart['qty'][(string) $ticket->id] ?? 0);
                if ($q <= 0) {
                    continue;
                }
                $unitCents = (int) round((float) $ticket->effectiveUnitPrice() * 100);
                if ($unitCents > 0) {
                    $stripeLineItems[] = [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => $ticket->name.$daySuffix.' — '.$event->title,
                            ],
                            'unit_amount' => $unitCents,
                        ],
                        'quantity' => $q,
                    ];
                    $payableTotal += $unitCents * $q;
                }
            }

            foreach ($event->additionalServices as $svc) {
                $q = (int) ($cart['addon_qty'][(string) $svc->id] ?? 0);
                if ($q <= 0) {
                    continue;
                }
                $unitCents = (int) round((float) $svc->price * 100);
                if ($unitCents > 0) {
                    $stripeLineItems[] = [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => $svc->name.' (add-on)'.$daySuffix.' — '.$event->title,
                            ],
                            'unit_amount' => $unitCents,
                        ],
                        'quantity' => $q,
                    ];
                    $payableTotal += $unitCents * $q;
                }
            }
        }

        return new self($payableTotal, $currency, $stripeLineItems);
    }
}
