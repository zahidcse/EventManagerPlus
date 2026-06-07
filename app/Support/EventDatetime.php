<?php

namespace App\Support;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;

class EventDatetime
{
    public static function parseWallClock(?string $date, ?string $time, string $timezone): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        $timePart = ($time === null || $time === '') ? '00:00' : $time;
        $tz = TimezoneList::normalize($timezone);

        return Carbon::parse($date.' '.$timePart, $tz)->utc();
    }

    public static function displayTimezone(?User $user, Event $event): string
    {
        if ($user !== null && filled($user->timezone) && TimezoneList::isValid($user->timezone)) {
            return (string) $user->timezone;
        }

        return $event->eventTimezone();
    }

    public static function atDisplay(?Carbon $instant, string $displayTimezone): ?Carbon
    {
        if ($instant === null) {
            return null;
        }

        return $instant->copy()->timezone(TimezoneList::normalize($displayTimezone));
    }
}
