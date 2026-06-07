<?php

namespace App\Models;

use App\Support\EventDatetime;
use App\Support\RichTextSanitizer;
use App\Support\TimezoneList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    protected $fillable = [
        'organizer_id',
        'title',
        'slug',
        'category',
        'event_category_id',
        'visibility',
        'description',
        'starts_at',
        'ends_at',
        'timezone',
        'schedule_type',
        'recurrence_weekdays',
        'recurrence_ends_on',
        'repeat_every_days',
        'custom_schedule_dates',
        'cover_image_path',
        'status',
        'location_type',
        'venue_street',
        'venue_city',
        'venue_state',
        'venue_postal',
        'venue_country',
        'streaming_platform',
        'meeting_url',
        'capacity',
        'global_ticket_quantity_enabled',
        'global_ticket_quantity',
        'registrations_count',
        'fee_handling',
        'max_tickets_per_customer',
        'meta_title',
        'meta_description',
        'email_subject',
        'email_body',
        'ticket_pdf_fields',
        'ticket_logo_path',
        'attendee_settings',
        'seat_plan_enabled',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'recurrence_weekdays' => 'array',
            'recurrence_ends_on' => 'date',
            'repeat_every_days' => 'integer',
            'custom_schedule_dates' => 'array',
            'capacity' => 'integer',
            'global_ticket_quantity_enabled' => 'boolean',
            'global_ticket_quantity' => 'integer',
            'registrations_count' => 'integer',
            'max_tickets_per_customer' => 'integer',
            'ticket_pdf_fields' => 'array',
            'attendee_settings' => 'array',
            'seat_plan_enabled' => 'boolean',
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultTicketPdfFields(): array
    {
        return [
            'company_logo' => true,
            'event_name' => true,
            'organizer_name' => true,
            'company_name' => true,
            'event_datetime' => true,
            'event_location' => true,
            'location_type' => true,
            'attendee_name' => true,
            'attendee_email' => true,
            'attendee_phone' => true,
            'ticket_type' => true,
            'seat_number' => true,
            'booking_id' => true,
            'session_date' => true,
            'tier_price' => true,
            'order_status' => true,
            'payment_reference' => true,
            'notes' => true,
            'checkin_qr' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function ticketPdfFieldsResolved(): array
    {
        $saved = is_array($this->ticket_pdf_fields) ? $this->ticket_pdf_fields : [];
        $defaults = self::defaultTicketPdfFields();
        foreach ($defaults as $key => $enabled) {
            if (array_key_exists($key, $saved)) {
                $defaults[$key] = filter_var($saved[$key], FILTER_VALIDATE_BOOLEAN);
            } else {
                $defaults[$key] = (bool) $enabled;
            }
        }

        return $defaults;
    }

    public function ticketLogoPublicUrl(): ?string
    {
        if (! filled($this->ticket_logo_path)) {
            return null;
        }

        return asset('uploads/'.$this->ticket_logo_path);
    }

    /**
     * @return array{enabled: bool, fields: array<string, bool>}
     */
    public static function defaultAttendeeSettings(): array
    {
        return [
            'enabled' => false,
            'fields' => [
                'name' => true,
                'email' => true,
                'phone' => true,
                'gender' => false,
                'driving_license' => false,
                'nid' => false,
                'location' => false,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function attendeeFieldDefinitions(): array
    {
        return [
            'name' => ['label' => 'Name', 'type' => 'text', 'autocomplete' => 'name'],
            'email' => ['label' => 'Email', 'type' => 'email', 'autocomplete' => 'email'],
            'phone' => ['label' => 'Phone', 'type' => 'tel', 'autocomplete' => 'tel'],
            'gender' => [
                'label' => 'Gender',
                'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Select'],
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female'],
                    ['value' => 'other', 'label' => 'Other'],
                ],
            ],
            'driving_license' => ['label' => 'Driving license', 'type' => 'text', 'autocomplete' => 'off'],
            'nid' => ['label' => 'NID', 'type' => 'text', 'autocomplete' => 'off'],
            'location' => ['label' => 'Location', 'type' => 'text', 'autocomplete' => 'street-address'],
        ];
    }

    /**
     * @return array{enabled: bool, fields: array<string, bool>}
     */
    public function attendeeSettingsResolved(): array
    {
        $saved = is_array($this->attendee_settings) ? $this->attendee_settings : [];
        $defaults = self::defaultAttendeeSettings();

        $rawFields = is_array($saved['fields'] ?? null) ? $saved['fields'] : [];
        $fields = [];
        foreach ($defaults['fields'] as $key => $defaultEnabled) {
            if (array_key_exists($key, $rawFields)) {
                $fields[$key] = filter_var($rawFields[$key], FILTER_VALIDATE_BOOLEAN);
            } else {
                $fields[$key] = (bool) $defaultEnabled;
            }
        }

        return [
            'enabled' => array_key_exists('enabled', $saved)
                ? filter_var($saved['enabled'], FILTER_VALIDATE_BOOLEAN)
                : (bool) $defaults['enabled'],
            'fields' => $fields,
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(EventFaq::class);
    }

    public function timelineItems(): HasMany
    {
        return $this->hasMany(EventTimelineItem::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    public function additionalServices(): HasMany
    {
        return $this->hasMany(EventAdditionalService::class);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(EventGalleryImage::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(EventCoupon::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * @return list<array{name?: string, price?: float|int|string, quantity?: int|string, sales_start?: ?string, sales_end?: ?string, early_bird_price?: float|int|string|null, early_bird_ends_at?: ?string}>
     */
    public function ticketRows(): array
    {
        $rows = $this->tickets()
            ->orderBy('sort_order')
            ->get()
            ->map(function (EventTicket $t) {
                return [
                    'name' => $t->name,
                    'price' => $t->price,
                    'quantity' => $t->quantity,
                    'sales_start' => $t->sales_start?->format('Y-m-d') ?? '',
                    'sales_end' => $t->sales_end?->format('Y-m-d') ?? '',
                    'early_bird_price' => $t->early_bird_price,
                    'early_bird_ends_at' => $t->early_bird_ends_at?->format('Y-m-d') ?? '',
                ];
            })
            ->all();

        return count($rows) > 0 ? $rows : [
            ['name' => '', 'price' => '', 'quantity' => '', 'sales_start' => '', 'sales_end' => '', 'early_bird_price' => '', 'early_bird_ends_at' => ''],
        ];
    }

    /**
     * @return list<array{name?: string, price?: float|int|string, quantity?: int|string}>
     */
    public function additionalServiceRows(): array
    {
        $rows = $this->additionalServices()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (EventAdditionalService $s) => [
                'name' => $s->name,
                'price' => $s->price,
                'quantity' => $s->quantity,
            ])
            ->all();

        return count($rows) > 0 ? $rows : [
            ['name' => '', 'price' => '', 'quantity' => ''],
        ];
    }

    /**
     * @return list<array{question?: string, answer?: string}>
     */
    public function faqRows(): array
    {
        $rows = $this->faqs()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (EventFaq $f) => [
                'question' => $f->question,
                'answer' => $f->answer ?? '',
            ])
            ->all();

        return count($rows) > 0 ? $rows : [
            ['question' => '', 'answer' => ''],
        ];
    }

    /**
     * @return list<array{time_label?: string, title?: string}>
     */
    public function timelineRows(): array
    {
        $rows = $this->timelineItems()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (EventTimelineItem $item) => [
                'time_label' => $item->time_label ?? '',
                'title' => $item->title,
            ])
            ->all();

        return count($rows) > 0 ? $rows : [
            ['time_label' => '', 'title' => ''],
        ];
    }

    /**
     * @return list<array{id?: int|string, code?: string, discount_type?: string, discount_value?: float|string, max_uses?: int|string|null, valid_from?: string, valid_until?: string, is_active?: bool}>
     */
    public function couponRows(): array
    {
        $rows = $this->coupons()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (EventCoupon $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'discount_type' => $c->discount_type,
                'discount_value' => $c->discount_value,
                'max_uses' => $c->max_uses,
                'valid_from' => $c->valid_from?->format('Y-m-d') ?? '',
                'valid_until' => $c->valid_until?->format('Y-m-d') ?? '',
                'is_active' => $c->is_active,
            ])
            ->all();

        return count($rows) > 0 ? $rows : [[
            'id' => '',
            'code' => '',
            'discount_type' => 'percent',
            'discount_value' => '',
            'max_uses' => '',
            'valid_from' => '',
            'valid_until' => '',
            'is_active' => true,
        ]];
    }

    protected static function booted(): void
    {
        static::deleting(function (Event $event): void {
            foreach ($event->galleryImages()->get() as $img) {
                if ($img->path) {
                    Storage::disk('uploads')->delete($img->path);
                }
            }
        });
    }

    public function descriptionHtml(): string
    {
        return RichTextSanitizer::html($this->description);
    }

    public function categoryLabel(): string
    {
        return $this->eventCategory?->name ?? (string) ($this->category ?? '');
    }

    public function eventTimezone(): string
    {
        return TimezoneList::normalize($this->timezone);
    }

    /**
     * Timezone used when showing this event on the public site.
     * Logged-in users with a saved timezone see events in that zone; guests use the event timezone.
     */
    public function displayTimezone(): string
    {
        return EventDatetime::displayTimezone(auth()->user(), $this);
    }

    public function usesViewerTimezone(): bool
    {
        $user = auth()->user();

        return $user !== null
            && filled($user->timezone)
            && TimezoneList::isValid($user->timezone)
            && $user->timezone !== $this->eventTimezone();
    }

    public function shouldShowTimezoneNotice(): bool
    {
        return $this->starts_at !== null;
    }

    public function timezoneDisplayLabel(?string $timezone = null): string
    {
        return TimezoneList::label(TimezoneList::normalize($timezone ?? $this->eventTimezone()));
    }

    public function timezoneNoticeMessage(): string
    {
        if ($this->usesViewerTimezone()) {
            return 'Times shown in your timezone ('.$this->timezoneDisplayLabel($this->displayTimezone()).').';
        }

        return 'Times shown in '.$this->timezoneDisplayLabel($this->eventTimezone()).'.';
    }

    /**
     * Compact suffix for inline display next to times, e.g. " · GMT+06:00".
     */
    public function timezoneInlineSuffix(): string
    {
        if ($this->starts_at === null) {
            return '';
        }

        return ' · '.TimezoneList::gmtLabel($this->displayTimezone());
    }

    public function formatDisplayTime(?Carbon $instant, bool $withTimezoneSuffix = true): string
    {
        if ($instant === null) {
            return '';
        }

        $formatted = $instant->format('g:i A');

        return $withTimezoneSuffix ? $formatted.$this->timezoneInlineSuffix() : $formatted;
    }

    /**
     * @param  string|null  $time  A time fragment (may include extra words like "each day").
     */
    public function appendTimezoneToTime(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return $time;
        }

        return trim($time).$this->timezoneInlineSuffix();
    }

    public function stripTimezoneInlineSuffix(string $text): string
    {
        $suffix = $this->timezoneInlineSuffix();
        if ($suffix !== '' && str_ends_with($text, $suffix)) {
            return rtrim(substr($text, 0, -strlen($suffix)));
        }

        return $text;
    }

    /**
     * Date/time line for the public event detail hero (timezone shown once at the end).
     */
    public function detailHeroWhenLine(): string
    {
        $startsAt = $this->startsAtDisplay();
        $endsAt = $this->endsAtDisplay();
        $tz = $this->timezoneInlineSuffix();
        $type = $this->schedule_type ?? 'single';

        if ($type === 'single' && $startsAt) {
            $whenParts = [$startsAt->format('D, M j · g:i A')];
            if ($endsAt) {
                $whenParts[] = 'Ends '.$endsAt->format('g:i A');
            }

            return implode(' · ', $whenParts).$tz;
        }

        $sidebar = $this->sidebarWhenLines();
        $date = trim((string) ($sidebar['date'] ?? ''));
        $time = trim((string) ($sidebar['time'] ?? ''));
        $segments = [];

        if ($date !== '') {
            $segment = $date;
            if ($time !== '') {
                $segment .= ' · '.$this->stripTimezoneInlineSuffix($time);
            }
            $segments[] = $segment;
        } elseif ($time !== '') {
            $segments[] = $this->stripTimezoneInlineSuffix($time);
        }

        if ($endsAt) {
            $segments[] = 'Ends '.$endsAt->format('g:i A');
        }

        $line = implode(' · ', array_filter($segments, static fn ($s) => $s !== ''));

        if ($line !== '' && $startsAt !== null) {
            $line .= $tz;
        }

        return $line;
    }

    /**
     * @deprecated Use detailHeroWhenLine() for hero; kept for backwards compatibility.
     */
    public function detailHeroSummaryLine(?string $address = null): string
    {
        $line = $this->detailHeroWhenLine();
        $addr = trim((string) $address);
        if ($addr !== '') {
            $line .= ($line !== '' ? ' · ' : '').$addr;
        }

        return $line;
    }

    public function startsAtDisplay(): ?Carbon
    {
        return EventDatetime::atDisplay($this->starts_at, $this->displayTimezone());
    }

    public function endsAtDisplay(): ?Carbon
    {
        return EventDatetime::atDisplay($this->ends_at, $this->displayTimezone());
    }

    /**
     * @return array{date: string, time: string|null}
     */
    public function adminWallClockFor(?Carbon $instant): array
    {
        if ($instant === null) {
            return ['date' => '', 'time' => ''];
        }

        $local = $instant->copy()->timezone($this->eventTimezone());

        return [
            'date' => $local->format('Y-m-d'),
            'time' => $local->format('H:i'),
        ];
    }

    /**
     * Date and time for the booking sidebar (time is separate so it is not duplicated).
     *
     * @return array{date: string, time: ?string}
     */
    public function sidebarWhenLines(): array
    {
        $startsAt = $this->startsAtDisplay();
        $type = $this->schedule_type ?? 'single';

        if ($type === 'custom_interval') {
            $dates = is_array($this->custom_schedule_dates) ? $this->custom_schedule_dates : [];
            $dates = array_values(array_unique(array_filter($dates, static fn ($d) => $d !== null && $d !== '')));
            sort($dates);
            if (count($dates) > 0) {
                $formatted = [];
                foreach (array_slice($dates, 0, 4) as $d) {
                    try {
                        $formatted[] = Carbon::parse($d)->format('M j, Y');
                    } catch (\Throwable) {
                        $formatted[] = (string) $d;
                    }
                }
                $line = implode(' · ', $formatted);
                $extra = count($dates) - 4;
                if ($extra > 0) {
                    $line .= ' · +'.$extra.' more';
                }
                $time = $startsAt ? $this->formatDisplayTime($startsAt, false) : '';

                return [
                    'date' => $line,
                    'time' => $time !== '' ? $this->appendTimezoneToTime($time.' each day') : null,
                ];
            }
            if ($this->repeat_every_days !== null && (int) $this->repeat_every_days >= 1) {
                $n = (int) $this->repeat_every_days;
                $start = $startsAt?->format('M j, Y') ?? 'TBD';
                $until = $this->recurrence_ends_on?->format('M j, Y') ?? '';
                $date = 'Every '.$n.' day'.($n === 1 ? '' : 's').' · from '.$start;
                if ($until !== '') {
                    $date .= ' · until '.$until;
                }
                $time = $startsAt ? $this->formatDisplayTime($startsAt) : '';

                return [
                    'date' => $date,
                    'time' => $time !== '' ? $time : null,
                ];
            }
        }

        if ($type === 'recurring') {
            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $indices = is_array($this->recurrence_weekdays) ? $this->recurrence_weekdays : [];
            $indices = array_values(array_unique(array_map('intval', $indices)));
            sort($indices, SORT_NUMERIC);
            if (count($indices) === 0) {
                if ($startsAt) {
                    return [
                        'date' => $startsAt->format('M j, Y'),
                        'time' => $this->formatDisplayTime($startsAt),
                    ];
                }

                return ['date' => 'TBD', 'time' => null];
            }
            $labels = [];
            foreach ($indices as $i) {
                $labels[] = $days[$i] ?? '?';
            }
            $until = $this->recurrence_ends_on?->format('M j, Y') ?? '';
            $date = 'Weekly · '.implode(', ', $labels);
            if ($until !== '') {
                $date .= ' · until '.$until;
            }
            $time = $startsAt ? $this->formatDisplayTime($startsAt) : '';

            return [
                'date' => $date,
                'time' => $time !== '' ? $time : null,
            ];
        }

        if ($type === 'single') {
            return [
                'date' => $this->dateRangeLabel(),
                'time' => $startsAt ? $this->formatDisplayTime($startsAt) : null,
            ];
        }

        return [
            'date' => $startsAt ? $startsAt->format('M j, Y') : 'TBD',
            'time' => $startsAt ? $this->formatDisplayTime($startsAt) : null,
        ];
    }

    public function dateRangeLabel(): string
    {
        if (($this->schedule_type ?? 'single') !== 'single') {
            return $this->scheduleSummaryLine();
        }

        $startsAt = $this->startsAtDisplay();
        $endsAt = $this->endsAtDisplay();

        if (! $startsAt) {
            return 'TBD';
        }

        if ($endsAt && $startsAt->isSameDay($endsAt)) {
            return $startsAt->format('M j, Y');
        }

        if ($endsAt) {
            return $startsAt->format('M j').'–'.$endsAt->format('M j, Y');
        }

        return $startsAt->format('M j, Y');
    }

    /**
     * Short “when” line for listings and sidebars (includes time for single events).
     */
    public function scheduleSummaryLine(): string
    {
        $startsAt = $this->startsAtDisplay();
        $type = $this->schedule_type ?? 'single';

        if ($type === 'recurring') {
            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $indices = is_array($this->recurrence_weekdays) ? $this->recurrence_weekdays : [];
            $indices = array_values(array_unique(array_map('intval', $indices)));
            sort($indices, SORT_NUMERIC);
            if (count($indices) === 0) {
                return $startsAt
                    ? $startsAt->format('D, M j').' · '.$this->formatDisplayTime($startsAt)
                    : 'TBD';
            }
            $labels = [];
            foreach ($indices as $i) {
                $labels[] = $days[$i] ?? '?';
            }
            $time = $startsAt ? $this->formatDisplayTime($startsAt) : '';
            $until = $this->recurrence_ends_on?->format('M j, Y') ?? '';

            return 'Weekly ('.implode(', ', $labels).')'
                .($time !== '' ? ' · '.$time : '')
                .($until !== '' ? ' · until '.$until : '');
        }

        if ($type === 'custom_interval') {
            $dates = is_array($this->custom_schedule_dates) ? $this->custom_schedule_dates : [];
            $dates = array_values(array_unique(array_filter($dates, static fn ($d) => $d !== null && $d !== '')));
            sort($dates);
            if (count($dates) > 0) {
                $time = $startsAt ? $this->formatDisplayTime($startsAt, false) : '';
                $formatted = [];
                foreach (array_slice($dates, 0, 4) as $d) {
                    try {
                        $formatted[] = Carbon::parse($d)->format('M j, Y');
                    } catch (\Throwable) {
                        $formatted[] = (string) $d;
                    }
                }
                $extra = count($dates) - 4;
                $line = implode(' · ', $formatted);
                if ($extra > 0) {
                    $line .= ' · +'.$extra.' more';
                }

                return $line.($time !== '' ? ' · '.$this->appendTimezoneToTime($time.' each day') : '');
            }
            if ($this->repeat_every_days !== null && (int) $this->repeat_every_days >= 1) {
                $n = (int) $this->repeat_every_days;
                $start = $startsAt?->format('M j, Y') ?? 'TBD';
                $time = $startsAt ? $this->formatDisplayTime($startsAt) : '';
                $until = $this->recurrence_ends_on?->format('M j, Y') ?? '';

                return 'Every '.$n.' day'.($n === 1 ? '' : 's')
                    .' · from '.$start
                    .($time !== '' ? ' · '.$time : '')
                    .($until !== '' ? ' · until '.$until : '');
            }
        }

        return $startsAt
            ? $startsAt->format('D, M j').' · '.$this->formatDisplayTime($startsAt)
            : 'TBD';
    }

    public function locationLabel(): string
    {
        if ($this->location_type === 'physical') {
            $line = $this->fullVenueAddressLine();

            return $line !== '' ? $line : 'TBD';
        }

        if ($this->location_type === 'hybrid') {
            $line = $this->fullVenueAddressLine();

            return $line !== '' ? $line : 'Hybrid';
        }

        if ($this->location_type === 'virtual' && ! $this->venue_city) {
            return 'Virtual (Remote)';
        }

        $parts = array_filter([
            $this->venue_city,
            $this->venue_state,
        ]);

        return count($parts) > 0 ? implode(', ', $parts) : ($this->location_type === 'virtual' ? 'Virtual (Remote)' : 'TBD');
    }

    public function registrationsProgressPercent(): int
    {
        if ($this->capacity <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->registrations_count / $this->capacity) * 100));
    }

    public function usesGlobalTicketQuantity(): bool
    {
        return (bool) $this->global_ticket_quantity_enabled;
    }

    /**
     * Shared inventory remaining when global quantity is enabled and capped (> 0).
     *
     * For recurring/custom schedules, pass an occurrence date (Y-m-d) to scope
     * the pool to that day. Single-schedule events always use overall totals.
     *
     * @return int|null Null when not using a finite global cap (unlimited pool).
     */
    public function remainingGlobalTicketPool(?string $occurrenceDate = null): ?int
    {
        if (! $this->usesGlobalTicketQuantity()) {
            return null;
        }

        $cap = (int) $this->global_ticket_quantity;
        if ($cap <= 0) {
            return null;
        }

        return max(0, $cap - $this->bookingsCountForInventory($occurrenceDate));
    }

    /**
     * Current booking rows counted against inventory.
     *
     * For recurring/custom schedules, when a valid occurrence date is provided,
     * counting is scoped to that day only.
     */
    public function bookingsCountForInventory(?string $occurrenceDate = null): int
    {
        $query = $this->bookings();
        if ($this->usesPerDayInventory()) {
            $day = $this->normalizeInventoryDate($occurrenceDate);
            if ($day !== null) {
                $query->whereDate('occurrence_date', $day);
            }
        }

        return (int) $query->count();
    }

    public function usesPerDayInventory(): bool
    {
        return ($this->schedule_type ?? 'single') !== 'single';
    }

    private function normalizeInventoryDate(?string $occurrenceDate): ?string
    {
        if ($occurrenceDate === null || trim($occurrenceDate) === '') {
            return null;
        }

        try {
            return Carbon::parse($occurrenceDate)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Physical / mailing-style address when the event has venue fields.
     */
    public function fullVenueAddressLine(): string
    {
        return implode(', ', array_filter([
            $this->venue_street,
            $this->venue_city,
            $this->venue_state,
            $this->venue_postal,
            $this->venue_country,
        ]));
    }

    public function mapEmbedUrl(): ?string
    {
        $q = trim($this->fullVenueAddressLine());
        if ($q === '') {
            return null;
        }

        return 'https://www.google.com/maps?q='.rawurlencode($q).'&output=embed';
    }

    /**
     * Unique occurrence dates (Y-m-d) for custom schedule events, sorted ascending.
     *
     * @return list<string>
     */
    public function normalizedCustomOccurrenceDateStrings(): array
    {
        $raw = is_array($this->custom_schedule_dates) ? $this->custom_schedule_dates : [];
        $out = [];
        foreach ($raw as $d) {
            if ($d === null || $d === '') {
                continue;
            }
            try {
                $out[] = Carbon::parse((string) $d)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }
        $out = array_values(array_unique($out));
        sort($out, SORT_STRING);

        return $out;
    }

    /**
     * Dates customers may book (today onward), for multi-session events. Empty for single-day events.
     *
     * @return list<string>
     */
    public function bookableOccurrenceDateStrings(int $max = 50): array
    {
        $type = $this->schedule_type ?? 'single';
        if ($type === 'single') {
            return [];
        }

        $today = now()->startOfDay();
        $endCap = $today->copy()->addYears(2);

        if ($type === 'custom_interval') {
            $out = [];
            foreach ($this->normalizedCustomOccurrenceDateStrings() as $ymd) {
                try {
                    $c = Carbon::parse($ymd)->startOfDay();
                } catch (\Throwable) {
                    continue;
                }
                if ($c->lt($today)) {
                    continue;
                }
                $out[] = $ymd;
                if (count($out) >= $max) {
                    break;
                }
            }

            return $out;
        }

        if ($type === 'recurring') {
            if ($this->starts_at === null) {
                return [];
            }
            $indices = is_array($this->recurrence_weekdays) ? $this->recurrence_weekdays : [];
            $indices = array_values(array_unique(array_map(static fn ($v) => (int) $v, $indices)));
            $indices = array_values(array_filter($indices, static fn (int $i) => $i >= 0 && $i <= 6));
            sort($indices, SORT_NUMERIC);
            if ($indices === []) {
                return [];
            }

            $seriesStart = $this->starts_at->copy()->startOfDay();
            $seriesEnd = $this->recurrence_ends_on !== null
                ? $this->recurrence_ends_on->copy()->endOfDay()
                : null;

            $out = [];
            $cursor = $today->copy();
            if ($cursor->lt($seriesStart)) {
                $cursor = $seriesStart->copy();
            }

            while (count($out) < $max && $cursor->lte($endCap)) {
                if ($seriesEnd !== null && $cursor->gt($seriesEnd)) {
                    break;
                }
                $w = (int) $cursor->dayOfWeek;
                if (in_array($w, $indices, true)) {
                    $out[] = $cursor->toDateString();
                }
                $cursor->addDay();
            }

            return $out;
        }

        return [];
    }

    /**
     * All scheduled occurrence dates for staff registration (includes past dates).
     *
     * @return list<string>
     */
    public function occurrenceDateStringsForStaffRegistration(int $max = 80): array
    {
        $type = $this->schedule_type ?? 'single';
        if ($type === 'single') {
            return [];
        }

        if ($type === 'custom_interval') {
            return array_slice($this->normalizedCustomOccurrenceDateStrings(), 0, $max);
        }

        if ($type === 'recurring') {
            if ($this->starts_at === null) {
                return [];
            }
            $indices = is_array($this->recurrence_weekdays) ? $this->recurrence_weekdays : [];
            $indices = array_values(array_unique(array_map(static fn ($v) => (int) $v, $indices)));
            $indices = array_values(array_filter($indices, static fn (int $i) => $i >= 0 && $i <= 6));
            sort($indices, SORT_NUMERIC);
            if ($indices === []) {
                return [];
            }

            $seriesStart = $this->starts_at->copy()->startOfDay();
            $seriesEnd = $this->recurrence_ends_on !== null
                ? $this->recurrence_ends_on->copy()->endOfDay()
                : $seriesStart->copy()->addYears(2)->endOfDay();

            $out = [];
            $cursor = $seriesStart->copy();
            while (count($out) < $max && $cursor->lte($seriesEnd)) {
                $w = (int) $cursor->dayOfWeek;
                if (in_array($w, $indices, true)) {
                    $out[] = $cursor->toDateString();
                }
                $cursor->addDay();
            }

            return $out;
        }

        return [];
    }

    /**
     * Whether this event is in progress at the given instant (defaults to now).
     * Active status plus schedule-aware window: single events use starts_at/ends_at;
     * recurring and custom-interval events use occurrence days and daily session times.
     */
    public function isInProgressNow(?Carbon $at = null): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $at = ($at ?? now())->copy()->utc();
        $type = $this->schedule_type ?? 'single';

        if ($type === 'single') {
            return $this->isInProgressSingleAt($at);
        }

        if (! in_array($type, ['recurring', 'custom_interval'], true)) {
            return false;
        }

        return $this->isInProgressMultiSessionAt($at);
    }

    private function isInProgressSingleAt(Carbon $at): bool
    {
        if ($this->starts_at === null) {
            return false;
        }

        if ($at->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $at->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    private function isInProgressMultiSessionAt(Carbon $at): bool
    {
        $tz = $this->eventTimezone();
        $local = $at->copy()->timezone($tz);

        if ($this->starts_at !== null) {
            $seriesStart = $this->starts_at->copy()->timezone($tz);
            if ($local->lt($seriesStart)) {
                return false;
            }
        }

        if ($this->recurrence_ends_on !== null) {
            $seriesEnd = $this->recurrence_ends_on->copy()->endOfDay()->timezone($tz);
            if ($local->gt($seriesEnd)) {
                return false;
            }
        }

        if (! $this->occursOnLocalDate($local->toDateString())) {
            return false;
        }

        return $this->isWithinDailySessionWindow($local);
    }

    private function occursOnLocalDate(string $ymd): bool
    {
        $type = $this->schedule_type ?? 'single';

        if ($type === 'custom_interval') {
            return in_array($ymd, $this->normalizedCustomOccurrenceDateStrings(), true);
        }

        if ($type === 'recurring') {
            $indices = is_array($this->recurrence_weekdays) ? $this->recurrence_weekdays : [];
            $indices = array_values(array_unique(array_map(static fn ($v) => (int) $v, $indices)));
            if ($indices === []) {
                return false;
            }

            try {
                $dayOfWeek = (int) Carbon::parse($ymd, $this->eventTimezone())->dayOfWeek;
            } catch (\Throwable) {
                return false;
            }

            return in_array($dayOfWeek, $indices, true);
        }

        return false;
    }

    private function isWithinDailySessionWindow(Carbon $local): bool
    {
        if ($this->starts_at === null) {
            return true;
        }

        $tz = $this->eventTimezone();
        $startLocal = $this->starts_at->copy()->timezone($tz);

        $sessionStart = $local->copy()->setTime(
            (int) $startLocal->format('H'),
            (int) $startLocal->format('i'),
            (int) $startLocal->format('s'),
        );

        if ($this->ends_at !== null) {
            $endLocal = $this->ends_at->copy()->timezone($tz);
            $sessionEnd = $local->copy()->setTime(
                (int) $endLocal->format('H'),
                (int) $endLocal->format('i'),
                (int) $endLocal->format('s'),
            );
            if ($sessionEnd->lte($sessionStart)) {
                $sessionEnd = $sessionEnd->copy()->addDay();
            }
        } else {
            $sessionEnd = $sessionStart->copy()->endOfDay();
        }

        return $local->gte($sessionStart) && $local->lte($sessionEnd);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePublicActive(Builder $query): Builder
    {
        return $query->where('visibility', 'public')->where('status', 'active');
    }

    /**
     * Events that still have at least one bookable/upcoming occurrence.
     *
     * Single events use starts_at (and ends_at when set). Recurring and custom-interval
     * events stay visible while the series end date is today or later, even after the
     * first session datetime has passed.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcomingForPublicListing(Builder $query, ?Carbon $at = null): Builder
    {
        $at = ($at ?? now())->copy();
        $today = $at->copy()->startOfDay();

        return $query->where(function (Builder $q) use ($at, $today) {
            $q->where(function (Builder $single) use ($at) {
                $single->where(function (Builder $s) {
                    $s->whereNull('schedule_type')->orWhere('schedule_type', 'single');
                })->where(function (Builder $s) use ($at) {
                    $s->where('starts_at', '>=', $at)
                        ->orWhere(function (Builder $ongoing) use ($at) {
                            $ongoing->whereNotNull('ends_at')->where('ends_at', '>=', $at);
                        });
                });
            })->orWhere(function (Builder $multi) use ($today) {
                $multi->whereIn('schedule_type', ['recurring', 'custom_interval'])
                    ->whereNotNull('starts_at')
                    ->where(function (Builder $m) use ($today) {
                        $m->whereNull('recurrence_ends_on')
                            ->orWhereDate('recurrence_ends_on', '>=', $today);
                    });
            });
        });
    }
}
