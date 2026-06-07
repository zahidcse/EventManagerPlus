<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Support\Collection;

final class EventRegistrationEmailContent
{
    /**
     * @param  Collection<int, EventBooking>  $bookingsForOrder
     * @return array{subject: string, body: string}
     */
    public static function build(Event $event, EventBooking $primary, Collection $bookingsForOrder, ?string $siteName = null): array
    {
        $subjectTpl = trim((string) $event->email_subject) !== ''
            ? (string) $event->email_subject
            : 'Registration Confirmed: {event_name}';

        $bodyTpl = trim((string) $event->email_body) !== ''
            ? (string) $event->email_body
            : 'Hi {attendee_name}, your registration for {event_name} on {event_date} is confirmed.';

        $replacements = self::placeholders($event, $primary, $bookingsForOrder, $siteName);

        return [
            'subject' => self::replace($subjectTpl, $replacements),
            'body' => self::replace($bodyTpl, $replacements),
        ];
    }

    /**
     * @param  Collection<int, EventBooking>  $bookingsForOrder
     * @return array<string, string>
     */
    public static function placeholders(Event $event, EventBooking $primary, Collection $bookingsForOrder, ?string $siteName = null): array
    {
        $ticketSummary = $bookingsForOrder
            ->groupBy(static function (EventBooking $b): string {
                $occKey = $b->occurrence_date !== null
                    ? $b->occurrence_date->format('Y-m-d')
                    : '';

                return (string) $b->event_ticket_id.'@'.$occKey;
            })
            ->sortBy(static function (Collection $group): string {
                /** @var EventBooking $first */
                $first = $group->first();
                $occ = $first->occurrence_date !== null ? $first->occurrence_date->format('Y-m-d') : '0000-00-00';
                $tid = str_pad((string) $first->event_ticket_id, 10, '0', STR_PAD_LEFT);

                return $occ.'|'.$tid;
            })
            ->map(function (Collection $group): string {
                /** @var EventBooking $first */
                $first = $group->first();
                $name = $first->relationLoaded('ticket') && $first->ticket
                    ? (string) $first->ticket->name
                    : 'Ticket';
                $line = $name;
                if ($first->occurrence_date !== null) {
                    $line .= ' ('.$first->occurrence_date->format('M j, Y').')';
                }
                $line .= ' × '.$group->count();

                return $line;
            })
            ->values()
            ->implode(', ');

        $sessionDateLine = '—';
        $occDates = $bookingsForOrder
            ->map(static fn (EventBooking $b) => $b->occurrence_date)
            ->filter()
            ->unique(static fn ($d) => $d->format('Y-m-d'))
            ->sortBy(static fn ($d) => $d->format('Y-m-d'))
            ->values();
        if ($occDates->isNotEmpty()) {
            $sessionDateLine = $occDates->map(static fn ($d) => $d->format('l, M j, Y'))->implode('; ');
        } elseif (($event->schedule_type ?? 'single') === 'single' && $event->starts_at) {
            $sessionDateLine = $event->starts_at->format('l, M j, Y');
        }

        return [
            '{event_name}' => $event->title,
            '{event_date}' => $event->dateRangeLabel(),
            '{event_location}' => $event->locationLabel(),
            '{session_date}' => $sessionDateLine,
            '{attendee_name}' => $primary->attendee_name,
            '{attendee_email}' => (string) $primary->email,
            '{attendee_phone}' => (string) ($primary->phone ?? ''),
            '{ticket_summary}' => $ticketSummary,
            '{site_name}' => $siteName ?? (string) config('app.name', 'Events'),
        ];
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private static function replace(string $template, array $replacements): string
    {
        return strtr($template, $replacements);
    }
}
