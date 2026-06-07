<?php

declare(strict_types=1);

namespace App\Services\AdminAi;

use App\Http\Requests\Admin\StoreOrganizerRequest;
use App\Http\Requests\Admin\StoreSpeakerRequest;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use App\Models\Speaker;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\OrganizerRepositoryInterface;
use App\Services\ReportAi\ReportAiCompletionClient;
use App\Services\ReportAi\ReportAiRuntimeSettings;
use App\Support\EventDatetime;
use App\Support\PersonName;
use App\Support\TimezoneList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

final class EventAssistantService
{
    public function __construct(
        private readonly ReportAiCompletionClient $ai,
        private readonly OrganizerRepositoryInterface $organizers,
        private readonly EventRepositoryInterface $events,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(string $instruction): array
    {
        $instruction = trim($instruction);
        if ($instruction === '') {
            return ['ok' => false, 'error' => 'Describe what to create (organizer, speaker, or event).'];
        }

        $runtime = ReportAiRuntimeSettings::resolve();
        if (! $runtime->isUsable()) {
            return ['ok' => false, 'error' => $runtime->unusableReason()];
        }

        $maxLen = max(200, min(8000, (int) config('report_ai.max_question_length', 900)));
        if (mb_strlen($instruction) > $maxLen) {
            return ['ok' => false, 'error' => 'Instruction is too long. Shorten to '.$maxLen.' characters or fewer.'];
        }

        $ai = $this->ai->completeGenericJson(EventAssistantPromptBuilder::systemPrompt(), $instruction);
        if (isset($ai['error'])) {
            return ['ok' => false, 'error' => $ai['error']];
        }

        $plan = $ai['data'] ?? null;
        if (! is_array($plan)) {
            return ['ok' => false, 'error' => 'The assistant returned an unexpected response.'];
        }

        $intent = strtolower(trim((string) ($plan['intent'] ?? 'clarify')));
        $reply = trim((string) ($plan['reply'] ?? ''));

        return match ($intent) {
            'create_organizer' => $this->actCreateOrganizer(is_array($plan['organizer'] ?? null) ? $plan['organizer'] : [], $reply),
            'create_speaker' => $this->actCreateSpeaker(is_array($plan['speaker'] ?? null) ? $plan['speaker'] : [], $reply),
            'create_event' => $this->actCreateEvent(is_array($plan['event'] ?? null) ? $plan['event'] : [], $reply),
            default => [
                'ok' => true,
                'intent' => 'clarify',
                'message' => $reply !== '' ? $reply : 'Could you specify whether you want to create an organizer, a speaker, or an event—and include key details (names, email for organizers, dates for events)?',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function actCreateOrganizer(array $payload, string $reply): array
    {
        if ($payload === []) {
            return ['ok' => false, 'error' => $reply !== '' ? $reply : 'No organizer details were extracted. Include at least name, company, and email.'];
        }

        $data = [
            'name' => PersonName::format(trim((string) ($payload['name'] ?? ''))),
            'company_name' => trim((string) ($payload['company_name'] ?? '')),
            'job_title' => isset($payload['job_title']) ? trim((string) $payload['job_title']) : null,
            'email' => strtolower(trim((string) ($payload['email'] ?? ''))),
            'phone' => isset($payload['phone']) ? trim((string) $payload['phone']) : null,
            'password' => isset($payload['password']) && (string) $payload['password'] !== '' ? (string) $payload['password'] : Str::password(16),
            'bio' => isset($payload['bio']) ? trim((string) $payload['bio']) : null,
            'country' => isset($payload['country']) ? strtoupper(trim((string) $payload['country'])) : null,
            'city' => isset($payload['city']) ? trim((string) $payload['city']) : null,
            'state' => isset($payload['state']) ? trim((string) $payload['state']) : null,
            'postal_code' => isset($payload['postal_code']) ? trim((string) $payload['postal_code']) : null,
            'latitude' => isset($payload['latitude']) && $payload['latitude'] !== '' ? $payload['latitude'] : null,
            'longitude' => isset($payload['longitude']) && $payload['longitude'] !== '' ? $payload['longitude'] : null,
            'status' => in_array((string) ($payload['status'] ?? 'active'), ['active', 'inactive'], true)
                ? (string) $payload['status']
                : 'active',
            'auto_approve_events' => false,
            'digest_notifications' => false,
        ];

        if ($data['company_name'] === '' && $data['name'] !== '') {
            $data['company_name'] = $data['name'];
        }

        if ($data['country'] !== null && mb_strlen($data['country']) !== 2) {
            $data['country'] = null;
        }

        $validator = Validator::make($data, (new StoreOrganizerRequest())->rules());
        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?? 'Validation failed.';

            return ['ok' => false, 'error' => $msg, 'hint' => $reply];
        }

        $clean = $validator->validated();
        unset($clean['photo']);
        $clean['auto_approve_events'] = false;
        $clean['digest_notifications'] = false;

        try {
            $organizer = $this->organizers->create($clean);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not save organizer: '.$e->getMessage()];
        }

        return [
            'ok' => true,
            'intent' => 'create_organizer',
            'message' => $reply !== '' ? $reply : 'Organizer created.',
            'organizer_id' => $organizer->id,
            'organizer_name' => $organizer->name,
            'edit_url' => route('admin.organizers.edit', $organizer),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function actCreateSpeaker(array $payload, string $reply): array
    {
        if ($payload === []) {
            return ['ok' => false, 'error' => $reply !== '' ? $reply : 'No speaker details were extracted. Include at least a name.'];
        }

        $data = [
            'name' => trim((string) ($payload['name'] ?? '')),
            'headline' => isset($payload['headline']) ? trim((string) $payload['headline']) : null,
            'bio' => isset($payload['bio']) ? trim((string) $payload['bio']) : null,
            'sort_order' => isset($payload['sort_order']) ? (int) $payload['sort_order'] : 0,
        ];

        $validator = Validator::make($data, (new StoreSpeakerRequest())->rules());
        if ($validator->fails()) {
            return ['ok' => false, 'error' => $validator->errors()->first() ?? 'Validation failed.', 'hint' => $reply];
        }

        $clean = $validator->validated();
        unset($clean['photo']);

        try {
            $speaker = Speaker::query()->create($clean);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not save speaker: '.$e->getMessage()];
        }

        return [
            'ok' => true,
            'intent' => 'create_speaker',
            'message' => $reply !== '' ? $reply : 'Speaker created.',
            'speaker_id' => $speaker->id,
            'speaker_name' => $speaker->name,
            'edit_url' => route('admin.speakers.edit', $speaker),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function actCreateEvent(array $payload, string $reply): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            $title = 'New event';
        }

        $organizerId = $this->resolveOrganizerId($payload);

        $categoryId = $this->resolveCategoryId($payload);

        $visibility = in_array((string) ($payload['visibility'] ?? 'public'), ['public', 'private'], true)
            ? (string) $payload['visibility']
            : 'public';

        $eventTimezone = TimezoneList::normalize($this->nullableString($payload['timezone'] ?? null));

        $startDate = $this->nullableString($payload['start_date'] ?? null);
        $startTime = $this->nullableString($payload['start_time'] ?? null);
        $startsAt = $startDate === null
            ? null
            : EventDatetime::parseWallClock(
                $startDate,
                ($startTime !== null && $startTime !== '') ? $startTime : '09:00',
                $eventTimezone,
            );

        $endDate = $this->nullableString($payload['end_date'] ?? null);
        $endTime = $this->nullableString($payload['end_time'] ?? null);
        $endsAt = $endDate === null
            ? null
            : EventDatetime::parseWallClock(
                $endDate,
                ($endTime !== null && $endTime !== '') ? $endTime : '17:00',
                $eventTimezone,
            );

        $status = in_array((string) ($payload['status'] ?? 'draft'), ['draft', 'active', 'completed'], true)
            ? (string) $payload['status']
            : 'draft';

        $description = isset($payload['description']) ? trim((string) $payload['description']) : null;

        $locationType = in_array((string) ($payload['location_type'] ?? 'physical'), ['physical', 'virtual', 'hybrid'], true)
            ? (string) $payload['location_type']
            : 'physical';

        $globalEnabled = (bool) ($payload['global_ticket_quantity_enabled'] ?? false);
        $globalQty = max(0, (int) ($payload['global_ticket_quantity'] ?? 0));
        if ($globalEnabled && $globalQty <= 0) {
            $globalEnabled = false;
            $globalQty = 0;
        }

        $ticketRows = $this->normalizeTicketsForAssistant(
            $payload['tickets'] ?? null,
            $globalEnabled,
            $globalQty,
        );
        $additionalRows = $this->normalizeAdditionalServicesForAssistant($payload['additional_services'] ?? null);

        if ($ticketRows === []) {
            $ticketRows = $this->defaultTicketRowsForDraft();
        }

        $capacity = $globalEnabled ? $globalQty : array_sum(array_column($ticketRows, 'quantity'));

        $streaming = $this->nullableString($payload['streaming_platform'] ?? null);
        if ($streaming !== null && ! in_array($streaming, ['zoom', 'teams', 'google_meet', 'custom'], true)) {
            $streaming = null;
        }
        $meetingUrl = $this->nullableString($payload['meeting_url'] ?? null);
        if ($meetingUrl !== null && filter_var($meetingUrl, FILTER_VALIDATE_URL) === false) {
            $meetingUrl = null;
        }

        $venueStreet = $this->nullableString($payload['venue_street'] ?? null);
        $venueCity = $this->nullableString($payload['venue_city'] ?? null);
        $venueState = $this->nullableString($payload['venue_state'] ?? null);
        $venuePostal = $this->nullableString($payload['venue_postal'] ?? null);
        $venueCountry = $this->nullableString($payload['venue_country'] ?? null);
        [$venueStreet, $venueCity, $venueState, $venuePostal, $venueCountry, $streaming, $meetingUrl] = $this->normalizeLocationFields(
            $locationType,
            $venueStreet,
            $venueCity,
            $venueState,
            $venuePostal,
            $venueCountry,
            $streaming,
            $meetingUrl,
        );

        try {
            $event = DB::transaction(function () use (
                $title,
                $organizerId,
                $categoryId,
                $visibility,
                $description,
                $startsAt,
                $endsAt,
                $status,
                $locationType,
                $eventTimezone,
                $venueStreet,
                $venueCity,
                $venueState,
                $venuePostal,
                $venueCountry,
                $ticketRows,
                $additionalRows,
                $capacity,
                $globalEnabled,
                $globalQty,
                $streaming,
                $meetingUrl,
                $payload,
            ): Event {
                $slug = $this->makeUniqueSlug($title);
                $categoryName = $categoryId !== null
                    ? EventCategory::query()->whereKey($categoryId)->value('name')
                    : null;

                $event = $this->events->create([
                    'organizer_id' => $organizerId,
                    'title' => $title,
                    'slug' => $slug,
                    'category' => $categoryName,
                    'event_category_id' => $categoryId,
                    'visibility' => $visibility,
                    'description' => $description,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'timezone' => $eventTimezone,
                    'schedule_type' => 'single',
                    'status' => $status,
                    'location_type' => $locationType,
                    'venue_street' => $venueStreet,
                    'venue_city' => $venueCity,
                    'venue_state' => $venueState,
                    'venue_postal' => $venuePostal,
                    'venue_country' => $venueCountry,
                    'streaming_platform' => $streaming,
                    'meeting_url' => $meetingUrl,
                    'capacity' => $capacity,
                    'global_ticket_quantity_enabled' => $globalEnabled,
                    'global_ticket_quantity' => $globalQty,
                    'fee_handling' => 'pass_to_buyer',
                ]);

                foreach ($ticketRows as $i => $row) {
                    $event->tickets()->create([
                        'sort_order' => $i,
                        'name' => $row['name'],
                        'price' => $row['price'],
                        'early_bird_price' => null,
                        'early_bird_ends_at' => null,
                        'quantity' => $row['quantity'],
                        'sales_start' => $row['sales_start'],
                        'sales_end' => $row['sales_end'],
                    ]);
                }

                foreach ($additionalRows as $i => $row) {
                    $event->additionalServices()->create([
                        'sort_order' => $i,
                        'name' => $row['name'],
                        'price' => $row['price'],
                    ]);
                }

                $speakerNames = $payload['speaker_names'] ?? null;
                if (is_array($speakerNames) && $speakerNames !== []) {
                    $pivot = [];
                    foreach ($this->resolveSpeakerIdsFromNames($speakerNames) as $i => $sid) {
                        $pivot[$sid] = ['sort_order' => $i];
                    }
                    if ($pivot !== []) {
                        $event->speakers()->sync($pivot);
                    }
                }

                return $event->refresh();
            });
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not create event: '.$e->getMessage()];
        }

        return [
            'ok' => true,
            'intent' => 'create_event',
            'message' => $this->formatEventCreatedMessage($reply, $status),
            'event_id' => $event->id,
            'event_title' => $event->title,
            'edit_url' => route('admin.events.edit', $event),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveOrganizerId(array $payload): ?int
    {
        $oid = $payload['organizer_id'] ?? null;
        if (is_numeric($oid)) {
            $id = (int) $oid;
            if (Organizer::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        $name = trim((string) ($payload['organizer_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $lower = mb_strtolower($name);

        $hit = Organizer::query()
            ->whereRaw('LOWER(name) = ?', [$lower])
            ->orWhereRaw('LOWER(company_name) = ?', [$lower])
            ->first();

        if ($hit !== null) {
            return (int) $hit->id;
        }

        return Organizer::query()
            ->where('name', 'like', '%'.$name.'%')
            ->orWhere('company_name', 'like', '%'.$name.'%')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveCategoryId(array $payload): ?int
    {
        $cid = $payload['event_category_id'] ?? null;
        if (is_numeric($cid)) {
            $id = (int) $cid;
            if (EventCategory::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        $name = trim((string) ($payload['event_category_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $cat = EventCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        return $cat !== null ? (int) $cat->id : null;
    }

    /**
     * @param  list<mixed>|null  $tickets
     * @return list<array{name: string, price: float, quantity: int, sales_start: ?string, sales_end: ?string}>
     */
    private function normalizeTicketsForAssistant(?array $tickets, bool $globalEnabled, int $globalQty): array
    {
        if (! is_array($tickets) || $tickets === []) {
            return [];
        }

        $out = [];
        foreach ($tickets as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $price = round((float) ($row['price'] ?? 0), 2);
            if ($globalEnabled) {
                $qty = 0;
            } else {
                $qty = max(0, (int) ($row['quantity'] ?? 0));
            }

            $out[] = [
                'name' => $name,
                'price' => $price,
                'quantity' => $qty,
                'sales_start' => null,
                'sales_end' => null,
            ];
        }

        return $out;
    }

    /**
     * @param  list<mixed>|null  $services
     * @return list<array{name: string, price: float}>
     */
    private function normalizeAdditionalServicesForAssistant(?array $services): array
    {
        if (! is_array($services) || $services === []) {
            return [];
        }

        $out = [];
        foreach ($services as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'name' => $name,
                'price' => round((float) ($row['price'] ?? 0), 2),
            ];
        }

        return $out;
    }

    /**
     * Placeholder ticket so the event can be saved; admin sets real tiers on the edit screen.
     *
     * @return list<array{name: string, price: float, quantity: int, sales_start: null, sales_end: null}>
     */
    private function defaultTicketRowsForDraft(): array
    {
        return [
            [
                'name' => 'General admission',
                'price' => 0.0,
                'quantity' => 0,
                'sales_start' => null,
                'sales_end' => null,
            ],
        ];
    }

    private function formatEventCreatedMessage(string $reply, string $status): string
    {
        $base = trim($reply) !== '' ? trim($reply) : 'Draft event created ('.$status.').';

        return $base.' Review category, visibility (public/private), dates, tickets, and organizer on the edit page.';
    }

    /**
     * @param  list<mixed>  $names
     * @return list<int>
     */
    private function resolveSpeakerIdsFromNames(array $names): array
    {
        $ids = [];
        foreach ($names as $raw) {
            $n = trim((string) $raw);
            if ($n === '') {
                continue;
            }
            $sp = Speaker::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($n)])
                ->first()
                ?? Speaker::query()->where('name', 'like', '%'.$n.'%')->orderBy('id')->first();
            if ($sp !== null) {
                $ids[] = (int) $sp->id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function nullableString(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    /**
     * Align venue/virtual fields with admin event forms (physical / virtual / hybrid).
     *
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string, 4: ?string, 5: ?string, 6: ?string}
     */
    private function normalizeLocationFields(
        string $locationType,
        ?string $venueStreet,
        ?string $venueCity,
        ?string $venueState,
        ?string $venuePostal,
        ?string $venueCountry,
        ?string $streaming,
        ?string $meetingUrl,
    ): array {
        if ($locationType === 'physical') {
            return [$venueStreet, $venueCity, $venueState, $venuePostal, $venueCountry, null, null];
        }

        if ($locationType === 'virtual') {
            return [null, null, null, null, null, $streaming, $meetingUrl];
        }

        return [$venueStreet, null, null, null, null, $streaming, $meetingUrl];
    }

    private function makeUniqueSlug(string $title, ?int $exceptId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'event';
        }
        $slug = $base;
        $i = 1;
        while (
            Event::query()
                ->where('slug', $slug)
                ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
