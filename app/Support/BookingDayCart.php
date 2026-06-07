<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\EventAdditionalService;
use Illuminate\Http\Request;

/**
 * Per-session-day ticket and add-on quantities for multi-session events.
 */
final class BookingDayCart
{
    public static function usesPerDayCart(Event $event): bool
    {
        return ($event->schedule_type ?? 'single') !== 'single';
    }

    /**
     * @return list<array{date: ?string, qty: array<string, int>, addon_qty: array<string, int>}>
     */
    public static function dayCartsFromRequest(Event $event, Request $request): array
    {
        if (! self::usesPerDayCart($event)) {
            return [self::singleCartFromFlatRequest($event, $request)];
        }

        $bookable = $event->bookableOccurrenceDateStrings();
        $qtyByDate = $request->input('qty_by_date', []);
        $addonByDate = $request->input('addon_qty_by_date', []);
        if (! is_array($qtyByDate)) {
            $qtyByDate = [];
        }
        if (! is_array($addonByDate)) {
            $addonByDate = [];
        }

        $out = [];
        foreach ($bookable as $d) {
            $qRow = isset($qtyByDate[$d]) && is_array($qtyByDate[$d]) ? $qtyByDate[$d] : [];
            $aRow = isset($addonByDate[$d]) && is_array($addonByDate[$d]) ? $addonByDate[$d] : [];
            $out[] = [
                'date' => $d,
                'qty' => self::normalizeQtyRow($event, $qRow),
                'addon_qty' => self::normalizeAddonRow($event, $aRow),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{date: ?string, qty: array<string, int>, addon_qty: array<string, int>}>
     */
    public static function dayCartsFromPayload(Event $event, array $payload): array
    {
        if (isset($payload['day_carts']) && is_array($payload['day_carts']) && $payload['day_carts'] !== []) {
            $publicBookableOnly = ($payload['_registration_context'] ?? '') !== 'staff';

            return self::sanitizeDayCartsFromPayload($event, $payload['day_carts'], $publicBookableOnly);
        }

        return self::legacyDayCartsFromPayload($event, $payload);
    }

    /**
     * Admin / staff registration form (may include dates outside the public booking window).
     *
     * @return list<array{date: ?string, qty: array<string, int>, addon_qty: array<string, int>}>
     */
    public static function dayCartsFromStaffRequest(Event $event, Request $request): array
    {
        if (! self::usesPerDayCart($event)) {
            $qtyRaw = $request->input('admin_qty', []);
            $addonRaw = $request->input('admin_addon_qty', []);

            return [[
                'date' => null,
                'qty' => self::normalizeQtyRow($event, is_array($qtyRaw) ? $qtyRaw : []),
                'addon_qty' => self::normalizeAddonRow($event, is_array($addonRaw) ? $addonRaw : []),
            ]];
        }

        $dates = $event->occurrenceDateStringsForStaffRegistration();
        $qtyByDate = $request->input('admin_qty_by_date', []);
        $addonByDate = $request->input('admin_addon_qty_by_date', []);
        if (! is_array($qtyByDate)) {
            $qtyByDate = [];
        }
        if (! is_array($addonByDate)) {
            $addonByDate = [];
        }

        $out = [];
        foreach ($dates as $d) {
            $qRow = isset($qtyByDate[$d]) && is_array($qtyByDate[$d]) ? $qtyByDate[$d] : [];
            $aRow = isset($addonByDate[$d]) && is_array($addonByDate[$d]) ? $addonByDate[$d] : [];
            $out[] = [
                'date' => $d,
                'qty' => self::normalizeQtyRow($event, $qRow),
                'addon_qty' => self::normalizeAddonRow($event, $aRow),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{date: ?string, qty: array<string, int>, addon_qty: array<string, int>}>
     */
    public static function activeDayCartsForPayload(Event $event, array $payload): array
    {
        return array_values(array_filter(
            self::dayCartsFromPayload($event, $payload),
            static fn (array $cart): bool => array_sum($cart['qty']) > 0,
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function totalTicketSeatCount(Event $event, array $payload): int
    {
        $sum = 0;
        foreach (self::dayCartsFromPayload($event, $payload) as $cart) {
            $sum += array_sum($cart['qty']);
        }

        return $sum;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public static function sessionDatesWithTicketsFromPayload(Event $event, array $payload): array
    {
        if (! self::usesPerDayCart($event)) {
            return [];
        }
        $dates = [];
        foreach (self::activeDayCartsForPayload($event, $payload) as $cart) {
            if (isset($cart['date']) && is_string($cart['date']) && $cart['date'] !== '') {
                $dates[] = $cart['date'];
            }
        }
        sort($dates, SORT_STRING);

        return array_values(array_unique($dates));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, int>
     */
    public static function sumQtyByTicketAcrossDays(Event $event, array $payload): array
    {
        $sums = [];
        foreach ($event->tickets as $t) {
            $sums[(string) $t->id] = 0;
        }
        foreach (self::dayCartsFromPayload($event, $payload) as $cart) {
            foreach ($cart['qty'] as $tid => $n) {
                $tid = (string) $tid;
                if (isset($sums[$tid])) {
                    $sums[$tid] += (int) $n;
                }
            }
        }

        return $sums;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, int>
     */
    private static function normalizeQtyRow(Event $event, array $row): array
    {
        $out = [];
        foreach ($event->tickets as $t) {
            $k = (string) $t->id;
            $raw = $row[$k] ?? $row[$t->id] ?? 0;
            $out[$k] = max(0, min(100, (int) $raw));
        }

        return $out;
    }

    public static function maxAddonQtyForService(EventAdditionalService $service): int
    {
        $remaining = $service->remainingForSale();

        return $remaining !== null ? min(50, $remaining) : 50;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, int>
     */
    private static function normalizeAddonRow(Event $event, array $row): array
    {
        $out = [];
        foreach ($event->additionalServices as $s) {
            $k = (string) $s->id;
            $raw = $row[$k] ?? $row[$s->id] ?? 0;
            $max = self::maxAddonQtyForService($s);
            $out[$k] = max(0, min($max, (int) $raw));
        }

        return $out;
    }

    private static function singleCartFromFlatRequest(Event $event, Request $request): array
    {
        $qtyRaw = $request->input('qty', []);
        $addonRaw = $request->input('addon_qty', []);

        return [
            'date' => null,
            'qty' => self::normalizeQtyRow($event, is_array($qtyRaw) ? $qtyRaw : []),
            'addon_qty' => self::normalizeAddonRow($event, is_array($addonRaw) ? $addonRaw : []),
        ];
    }

    /**
     * @param  list<mixed>  $rows
     * @return list<array{date: ?string, qty: array<string, int>, addon_qty: array<string, int>}>
     */
    private static function sanitizeDayCartsFromPayload(Event $event, array $rows, bool $publicBookableOnly = true): array
    {
        if (! self::usesPerDayCart($event)) {
            $first = isset($rows[0]) && is_array($rows[0]) ? $rows[0] : [];

            return [[
                'date' => null,
                'qty' => self::normalizeQtyRow($event, is_array($first['qty'] ?? null) ? $first['qty'] : []),
                'addon_qty' => self::normalizeAddonRow($event, is_array($first['addon_qty'] ?? null) ? $first['addon_qty'] : []),
            ]];
        }

        $dateList = $publicBookableOnly
            ? $event->bookableOccurrenceDateStrings()
            : $event->occurrenceDateStringsForStaffRegistration();
        $allowed = array_fill_keys($dateList, true);
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $d = $row['date'] ?? null;
            if (! is_string($d) || $d === '' || ! isset($allowed[$d])) {
                continue;
            }
            $out[] = [
                'date' => $d,
                'qty' => self::normalizeQtyRow($event, is_array($row['qty'] ?? null) ? $row['qty'] : []),
                'addon_qty' => self::normalizeAddonRow($event, is_array($row['addon_qty'] ?? null) ? $row['addon_qty'] : []),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{date: ?string, qty: array<string, int>, addon_qty: array<string, int>}>
     */
    private static function legacyDayCartsFromPayload(Event $event, array $payload): array
    {
        if (! self::usesPerDayCart($event)) {
            $qty = [];
            foreach ($event->tickets as $t) {
                $k = (string) $t->id;
                $qty[$k] = max(0, min(100, (int) ($payload['qty'][$k] ?? $payload['qty'][$t->id] ?? 0)));
            }
            $addon = [];
            foreach ($event->additionalServices as $s) {
                $k = (string) $s->id;
                $max = self::maxAddonQtyForService($s);
                $addon[$k] = max(0, min($max, (int) ($payload['addon_qty'][$k] ?? 0)));
            }

            return [['date' => null, 'qty' => $qty, 'addon_qty' => $addon]];
        }

        $dates = [];
        if (isset($payload['occurrence_dates']) && is_array($payload['occurrence_dates'])) {
            foreach ($payload['occurrence_dates'] as $d) {
                if (is_string($d) && $d !== '') {
                    $dates[$d] = true;
                }
            }
        }
        if ($dates === [] && isset($payload['occurrence_date']) && is_string($payload['occurrence_date']) && $payload['occurrence_date'] !== '') {
            $dates[$payload['occurrence_date']] = true;
        }
        $dates = array_keys($dates);
        sort($dates, SORT_STRING);

        if ($dates === []) {
            return [];
        }

        $qtyRow = [];
        foreach ($event->tickets as $t) {
            $k = (string) $t->id;
            $qtyRow[$k] = max(0, min(100, (int) ($payload['qty'][$k] ?? 0)));
        }
        $addonRow = [];
        foreach ($event->additionalServices as $s) {
            $k = (string) $s->id;
            $max = self::maxAddonQtyForService($s);
            $addonRow[$k] = max(0, min($max, (int) ($payload['addon_qty'][$k] ?? 0)));
        }

        $carts = [];
        foreach ($dates as $d) {
            $carts[] = [
                'date' => $d,
                'qty' => $qtyRow,
                'addon_qty' => $addonRow,
            ];
        }

        return $carts;
    }
}
