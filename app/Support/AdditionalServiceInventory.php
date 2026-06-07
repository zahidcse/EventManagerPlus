<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\EventAdditionalService;

/**
 * Tracks and decrements per-service add-on stock (quantity column; 0 = unlimited).
 */
final class AdditionalServiceInventory
{
    /**
     * @return array<string, int> service id => total qty ordered
     */
    public static function totalsFromPayload(Event $event, array $payload): array
    {
        $totals = [];
        foreach (BookingDayCart::dayCartsFromPayload($event, $payload) as $cart) {
            foreach ($cart['addon_qty'] as $id => $n) {
                $n = (int) $n;
                if ($n <= 0) {
                    continue;
                }
                $id = (string) $id;
                $totals[$id] = ($totals[$id] ?? 0) + $n;
            }
        }

        return $totals;
    }

    public static function validate(Event $event, array $payload): ?string
    {
        $totals = self::totalsFromPayload($event, $payload);
        if ($totals === []) {
            return null;
        }

        $services = $event->relationLoaded('additionalServices')
            ? $event->additionalServices->keyBy(fn (EventAdditionalService $s) => (string) $s->id)
            : EventAdditionalService::query()
                ->where('event_id', $event->id)
                ->whereIn('id', array_keys($totals))
                ->get()
                ->keyBy(fn (EventAdditionalService $s) => (string) $s->id);

        foreach ($totals as $id => $qty) {
            $svc = $services->get($id);
            if ($svc === null) {
                return 'Invalid add-on quantity.';
            }

            if ($qty < 0) {
                return 'Invalid add-on quantity.';
            }

            $remaining = $svc->remainingForSale();
            if ($remaining !== null && $qty > $remaining) {
                return 'Not enough "'.$svc->name.'" left in stock.';
            }
        }

        return null;
    }

    /**
     * Lock rows, re-check stock, and decrement quantities. Call inside a DB transaction.
     */
    public static function decrement(Event $event, array $payload): ?string
    {
        $totals = self::totalsFromPayload($event, $payload);
        if ($totals === []) {
            return null;
        }

        $services = EventAdditionalService::query()
            ->where('event_id', $event->id)
            ->whereIn('id', array_keys($totals))
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (EventAdditionalService $s) => (string) $s->id);

        foreach ($totals as $id => $qty) {
            $svc = $services->get($id);
            if ($svc === null) {
                return 'Invalid add-on quantity.';
            }

            if ($svc->hasUnlimitedQuantity()) {
                continue;
            }

            if ($qty > $svc->quantity) {
                return 'Not enough "'.$svc->name.'" left in stock.';
            }
        }

        foreach ($totals as $id => $qty) {
            $svc = $services->get($id);
            if ($svc === null || $svc->hasUnlimitedQuantity()) {
                continue;
            }

            $svc->decrement('quantity', $qty);
        }

        return null;
    }
}
