<?php

namespace App\Support;

class TimezoneList
{
    /**
     * @return list<string>
     */
    public static function identifiers(): array
    {
        return \DateTimeZone::listIdentifiers();
    }

    public static function isValid(?string $timezone): bool
    {
        if ($timezone === null || $timezone === '') {
            return false;
        }

        return in_array($timezone, self::identifiers(), true);
    }

    public static function normalize(?string $timezone, string $fallback = 'UTC'): string
    {
        if (self::isValid($timezone)) {
            return (string) $timezone;
        }

        return self::isValid($fallback) ? $fallback : 'UTC';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function groupedForSelect(): array
    {
        $groups = [];
        foreach (self::identifiers() as $id) {
            $parts = explode('/', $id, 2);
            $region = $parts[0] ?? 'Other';
            $groups[$region][] = $id;
        }
        ksort($groups);

        return $groups;
    }

    public static function gmtLabel(string $timezone): string
    {
        try {
            $tz = new \DateTimeZone(self::normalize($timezone));
            $offset = $tz->getOffset(new \DateTimeImmutable('now', $tz));
            $hours = intdiv(abs($offset), 3600);
            $minutes = intdiv(abs($offset) % 3600, 60);
            $sign = $offset >= 0 ? '+' : '-';

            return sprintf('GMT%s%02d:%02d', $sign, $hours, $minutes);
        } catch (\Throwable) {
            return 'GMT';
        }
    }

    public static function label(string $timezone): string
    {
        try {
            return str_replace('_', ' ', self::normalize($timezone)).' ('.self::gmtLabel($timezone).')';
        } catch (\Throwable) {
            return str_replace('_', ' ', $timezone);
        }
    }

    /**
     * @return list<array{value: string, label: string, region: string}>
     */
    public static function searchOptions(): array
    {
        $out = [];
        foreach (self::identifiers() as $id) {
            $parts = explode('/', $id, 2);
            $out[] = [
                'value' => $id,
                'label' => self::label($id),
                'region' => $parts[0] ?? 'Other',
            ];
        }

        return $out;
    }
}
