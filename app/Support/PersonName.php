<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PersonName
{
    public static function format(?string $name): string
    {
        if ($name === null || trim($name) === '') {
            return '';
        }

        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        return collect($parts)
            ->map(fn (string $part): string => Str::title(mb_strtolower($part)))
            ->join(' ');
    }
}
