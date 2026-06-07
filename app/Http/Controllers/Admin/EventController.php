<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Event\StoreAdminEventRegistrationRequest;
use App\Models\SiteSetting;
use App\Support\Edition;
use App\Http\Requests\Admin\Event\StoreEventBasicRequest;
use App\Http\Requests\Admin\Event\UpdateEventBasicRequest;
use App\Http\Requests\Admin\Event\UpdateEventContentRequest;
use App\Http\Requests\Admin\Event\UpdateEventLocationRequest;
use App\Http\Requests\Admin\Event\UpdateEventMediaRequest;
use App\Http\Requests\Admin\Event\UpdateEventSpeakersRequest;
use App\Http\Requests\Admin\Event\UpdateEventTicketsRequest;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\EventCategory;
use App\Models\EventCoupon;
use App\Models\EventGalleryImage;
use App\Models\Organizer;
use App\Models\Speaker;
use App\Models\User;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Services\EventBookingConfirmationNotifier;
use App\Support\BookingDayCart;
use App\Support\EventDatetime;
use App\Support\PublicBookingPayload;
use App\Support\PublicUploadStorage;
use App\Support\TimezoneList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly EventBookingConfirmationNotifier $bookingConfirmationNotifier,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        if (! in_array($status, ['draft', 'active', 'completed'], true)) {
            $status = null;
        }
        $timeRange = $request->query('time');
        if (! in_array($timeRange, ['this_week', 'this_month', 'next_3_months'], true)) {
            $timeRange = null;
        }
        $search = $request->query('q');

        return view('admin.events.index', [
            'activeNav' => 'events',
            'events' => $this->events->paginateFiltered(
                10,
                $status,
                $timeRange,
                is_string($search) ? $search : null,
            ),
            'stats' => $this->events->dashboardStats(),
            'statusFilter' => $status ?? 'all',
            'timeFilter' => $timeRange ?? 'all',
        ]);
    }

    public function create(): View
    {
        return view('admin.events.create', [
            'activeNav' => 'events',
            'event' => null,
            'organizers' => Organizer::query()->orderBy('name')->get(),
            'eventCategories' => EventCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'allSpeakers' => Speaker::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function edit(Event $event): View
    {
        $event->load(['speakers']);

        return view('admin.events.create', [
            'activeNav' => 'events',
            'event' => $event,
            'organizers' => Organizer::query()->orderBy('name')->get(),
            'eventCategories' => EventCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'allSpeakers' => Speaker::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreEventBasicRequest $request): RedirectResponse
    {
        $data = $this->withLocationFieldsNormalized($this->basicPayloadFromRequest($request->safe()->all(), null));
        $event = $this->events->create($data);

        $this->syncEventSpeakerPivot($event, $request->input('event_speakers', []));

        return redirect()
            ->route('admin.events.edit.tickets', $event)
            ->with('success', 'Event created. Continue through ticketing, content, then advanced settings.');
    }

    public function update(UpdateEventBasicRequest $request, Event $event): RedirectResponse
    {
        $data = $this->withLocationFieldsNormalized($this->basicPayloadFromRequest($request->safe()->all(), $event));
        if ($request->input('wizard_action') === 'draft') {
            $data['status'] = 'draft';
        }

        DB::transaction(function () use ($request, $event, $data): void {
            $this->events->update($event, $data);
            $this->syncEventSpeakerPivot($event, $request->input('event_speakers', []));
        });

        if ($request->input('wizard_action') === 'draft') {
            return back()->with('success', $this->wizardStepSavedMessage());
        }

        return redirect()
            ->route('admin.events.edit.tickets', $event)
            ->with('success', 'Basics saved. Continue with ticketing.');
    }

    public function media(Event $event): RedirectResponse
    {
        return redirect()
            ->route('admin.events.edit', $event);
    }

    public function updateMedia(UpdateEventMediaRequest $request, Event $event): RedirectResponse
    {
        if ($request->hasFile('cover_image')) {
            if ($event->cover_image_path) {
                Storage::disk('uploads')->delete($event->cover_image_path);
            }
            $file = $request->file('cover_image');
            if ($file && $file->isValid()) {
                $path = PublicUploadStorage::store($file, 'events/covers');
                if ($path === null) {
                    return back()->withErrors(['cover_image' => 'Could not store the uploaded image. Please try again.']);
                }

                $this->events->update($event, [
                    'cover_image_path' => $path,
                ]);
            }
        }

        if ($request->input('wizard_action') === 'draft') {
            $this->events->update($event, ['status' => 'draft']);

            return back()->with('success', $this->wizardStepSavedMessage());
        }

        return redirect()
            ->route('admin.events.edit.content', $event)
            ->with('success', 'Media saved.');
    }

    public function uploadGalleryImage(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:6144'],
        ]);

        $file = $request->file('image');
        if (! $file || ! $file->isValid()) {
            return response()->json(['message' => 'Invalid upload.'], 422);
        }

        $path = PublicUploadStorage::store($file, 'events/gallery');
        if ($path === null) {
            return response()->json(['message' => 'Could not store the uploaded image. Please try again.'], 422);
        }
        $maxSort = (int) EventGalleryImage::query()->where('event_id', $event->id)->max('sort_order');
        $image = EventGalleryImage::query()->create([
            'event_id' => $event->id,
            'path' => $path,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'id' => $image->id,
            'url' => asset('uploads/'.$image->path),
        ]);
    }

    public function uploadHeroImage(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:6144'],
        ]);

        $file = $request->file('image');
        if (! $file || ! $file->isValid()) {
            return response()->json(['message' => 'Invalid upload.'], 422);
        }

        $event->refresh();

        if ($event->cover_image_path) {
            Storage::disk('uploads')->delete($event->cover_image_path);
        }

        $path = PublicUploadStorage::store($file, 'events/covers');
        if ($path === null) {
            return response()->json(['message' => 'Could not store the uploaded image. Please try again.'], 422);
        }
        $this->events->update($event, [
            'cover_image_path' => $path,
        ]);

        return response()->json([
            'url' => asset('uploads/'.$path),
        ]);
    }

    public function destroyGalleryImage(Event $event, EventGalleryImage $galleryImage): JsonResponse
    {
        if ((int) $galleryImage->event_id !== (int) $event->id) {
            abort(404);
        }

        if ($galleryImage->path) {
            Storage::disk('uploads')->delete($galleryImage->path);
        }
        $galleryImage->delete();

        return response()->json(['ok' => true]);
    }

    public function location(Event $event): RedirectResponse
    {
        return redirect()
            ->route('admin.events.edit', $event);
    }

    public function updateLocation(UpdateEventLocationRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();
        if (($data['location_type'] ?? '') === 'physical') {
            $data['streaming_platform'] = null;
            $data['meeting_url'] = null;
        }
        $this->events->update($event, $data);
        if ($request->input('wizard_action') === 'draft') {
            $this->events->update($event, ['status' => 'draft']);

            return back()->with('success', $this->wizardStepSavedMessage());
        }

        return redirect()
            ->route('admin.events.edit.tickets', $event)
            ->with('success', 'Location saved.');
    }

    public function tickets(Event $event): View
    {
        $event->load([
            'tickets' => static fn ($q) => $q->orderBy('sort_order'),
            'additionalServices' => static fn ($q) => $q->orderBy('sort_order'),
        ]);

        return view('admin.events.tickets', [
            'activeNav' => 'events',
            'event' => $event,
        ]);
    }

    public function registerAttendee(Event $event): View
    {
        $event->load([
            'tickets' => static fn ($q) => $q->orderBy('sort_order'),
            'additionalServices' => static fn ($q) => $q->orderBy('sort_order'),
        ]);

        return view('admin.events.register-attendee', [
            'activeNav' => 'events',
            'event' => $event,
            'registrationCustomers' => User::query()
                ->where('is_admin', false)
                ->orderBy('name')
                ->limit(1000)
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function storeRegistration(StoreAdminEventRegistrationRequest $request, Event $event): RedirectResponse
    {
        $event->load([
            'tickets' => static fn ($q) => $q->orderBy('sort_order'),
            'additionalServices' => static fn ($q) => $q->orderBy('sort_order'),
        ]);

        $customerUser = null;
        if ((string) $request->input('registration_kind') === 'registered_user') {
            $customerUser = User::query()
                ->whereKey((int) $request->input('user_id'))
                ->where('is_admin', false)
                ->first();

            if ($customerUser === null) {
                return back()
                    ->withErrors(['user_id' => 'Select a valid customer account.'])
                    ->withInput();
            }
        }

        $attendee = $request->attendeePayload($customerUser);

        $dayCarts = BookingDayCart::dayCartsFromStaffRequest($event, $request);

        $qty = [];
        $addonQty = [];
        foreach ($dayCarts as $c) {
            foreach ($c['qty'] as $id => $n) {
                $n = (int) $n;
                if ($n > 0) {
                    $id = (string) $id;
                    $qty[$id] = ($qty[$id] ?? 0) + $n;
                }
            }
            foreach ($c['addon_qty'] as $id => $n) {
                $n = (int) $n;
                if ($n > 0) {
                    $id = (string) $id;
                    $addonQty[$id] = ($addonQty[$id] ?? 0) + $n;
                }
            }
        }

        $occurrenceDates = BookingDayCart::sessionDatesWithTicketsFromPayload($event, [
            'day_carts' => $dayCarts,
            '_registration_context' => 'staff',
        ]);

        $payload = [
            'attendee_name' => $attendee['attendee_name'],
            'email' => $attendee['email'],
            'phone' => $attendee['phone'],
            'user_id' => $customerUser !== null ? (int) $customerUser->getKey() : null,
            'day_carts' => $dayCarts,
            '_registration_context' => 'staff',
            'qty' => $qty,
            'addon_qty' => $addonQty,
            'occurrence_dates' => $occurrenceDates,
        ];

        $inventoryError = PublicBookingPayload::validateInventory($event, $payload, true);
        if ($inventoryError !== null) {
            return back()->withErrors(['admin_qty' => $inventoryError])->withInput();
        }

        $adminNotes = trim((string) $request->input('admin_notes', ''));
        $staffLine = $adminNotes !== '' ? '[Staff registration] '.$adminNotes : '[Staff registration]';

        try {
            $created = DB::transaction(function () use ($event, $payload, $staffLine) {
                $addonError = \App\Support\AdditionalServiceInventory::decrement($event, $payload);
                if ($addonError !== null) {
                    throw new \RuntimeException($addonError);
                }

                return EventBooking::createManyFromCartPayload($event, $payload, [
                    'user_id' => $payload['user_id'],
                    'attendee_name' => $payload['attendee_name'],
                    'email' => $payload['email'],
                    'phone' => $payload['phone'],
                    'status' => 'confirmed',
                    'notes' => $staffLine,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['admin_qty' => $e->getMessage()])->withInput();
        }

        if ($request->boolean('send_confirmation') && $created->isNotEmpty()) {
            $this->bookingConfirmationNotifier->notify($event, $created);
        }

        return redirect()
            ->route('admin.events.register-attendee', $event)
            ->with('success', 'Attendee registered successfully.');
    }

    public function updateTickets(UpdateEventTicketsRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();
        $globalEnabled = (bool) ($validated['global_ticket_quantity_enabled'] ?? false);
        $globalQty = max(0, (int) ($validated['global_ticket_quantity'] ?? 0));
        $preserveEarlyBird = ! Edition::allowsEarlyBirdPricing();
        $existingEarlyBirdByName = [];
        if ($preserveEarlyBird) {
            $event->loadMissing('tickets');
            foreach ($event->tickets as $ticket) {
                $existingEarlyBirdByName[$ticket->name] = [
                    'early_bird_price' => $ticket->early_bird_price,
                    'early_bird_ends_at' => $ticket->early_bird_ends_at,
                    'sales_start' => $ticket->sales_start,
                ];
            }
        }
        $ticketData = $this->normalizedTicketsPayload(
            $validated['tickets'] ?? [],
            $globalEnabled,
            $globalQty,
            $preserveEarlyBird ? $existingEarlyBirdByName : null,
        );
        $syncAdditionalServices = Edition::allowsAdditionalServices();
        $additionalRows = $syncAdditionalServices
            ? $this->normalizedAdditionalServicesPayload($validated['additional_services'] ?? null)
            : [];
        $couponRows = $this->normalizedCouponsPayload($validated['coupons'] ?? null);

        DB::transaction(function () use ($event, $ticketData, $additionalRows, $couponRows, $globalEnabled, $globalQty, $syncAdditionalServices): void {
            $event->tickets()->delete();
            foreach ($ticketData['rows'] as $i => $row) {
                $event->tickets()->create([
                    'sort_order' => $i,
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'early_bird_price' => $row['early_bird_price'],
                    'early_bird_ends_at' => $row['early_bird_ends_at'],
                    'quantity' => $row['quantity'],
                    'sales_start' => $row['sales_start'],
                    'sales_end' => $row['sales_end'],
                ]);
            }

            if ($syncAdditionalServices) {
                $event->additionalServices()->delete();
                foreach ($additionalRows as $i => $row) {
                    $event->additionalServices()->create([
                        'sort_order' => $i,
                        'name' => $row['name'],
                        'price' => $row['price'],
                        'quantity' => $row['quantity'],
                    ]);
                }
            }

            $this->syncEventCoupons($event, $couponRows);

            $this->events->update($event, [
                'capacity' => $ticketData['capacity'],
                'global_ticket_quantity_enabled' => $globalEnabled,
                'global_ticket_quantity' => $globalQty,
            ]);
        });
        if ($request->input('wizard_action') === 'draft') {
            $this->events->update($event, ['status' => 'draft']);

            return back()->with('success', $this->wizardStepSavedMessage());
        }

        return redirect()
            ->route('admin.events.edit.content', $event)
            ->with('success', 'Ticketing saved.');
    }

    public function speakers(Event $event): RedirectResponse
    {
        return redirect()
            ->route('admin.events.edit', $event);
    }

    public function updateSpeakers(UpdateEventSpeakersRequest $request, Event $event): RedirectResponse
    {
        DB::transaction(function () use ($request, $event): void {
            $this->syncEventSpeakerPivot($event, $request->input('event_speakers', []));
            if ($request->input('wizard_action') === 'draft') {
                $this->events->update($event, ['status' => 'draft']);
            }
        });

        if ($request->input('wizard_action') === 'draft') {
            return back()->with('success', $this->wizardStepSavedMessage());
        }

        return redirect()
            ->route('admin.events.edit.content', $event)
            ->with('success', 'Speakers saved.');
    }

    public function content(Request $request, Event $event): View
    {
        $event->load([
            'galleryImages' => static fn ($q) => $q->orderBy('sort_order'),
        ]);

        $wizardPanel = $request->query('panel') === 'advanced' ? 'advanced' : 'content';

        return view('admin.events.content', [
            'activeNav' => 'events',
            'event' => $event,
            'wizardPanel' => $wizardPanel,
            'seatPlanEnabled' => false,
        ]);
    }

    public function updateContent(UpdateEventContentRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();
        $wizardPanel = ($validated['wizard_panel'] ?? 'content') === 'advanced' ? 'advanced' : 'content';

        if ($wizardPanel === 'content') {
            $faqs = $this->normalizedFaqs($validated['faqs'] ?? []);
            $timeline = $this->normalizedTimelineItems($validated['timeline'] ?? []);

            DB::transaction(function () use ($event, $validated, $faqs, $timeline): void {
                $event->faqs()->delete();
                if (is_array($faqs)) {
                    foreach (array_values($faqs) as $i => $row) {
                        $event->faqs()->create([
                            'sort_order' => $i,
                            'question' => $row['question'],
                            'answer' => $row['answer'] !== '' ? $row['answer'] : null,
                        ]);
                    }
                }

                $event->timelineItems()->delete();
                if (is_array($timeline)) {
                    foreach (array_values($timeline) as $i => $row) {
                        $event->timelineItems()->create([
                            'sort_order' => $i,
                            'time_label' => $row['time_label'] !== '' ? $row['time_label'] : null,
                            'title' => $row['title'],
                            'description' => null,
                        ]);
                    }
                }

                $this->events->update($event, [
                    'meta_title' => $validated['meta_title'] ?? null,
                    'meta_description' => $validated['meta_description'] ?? null,
                ]);
            });
        } else {
            $updatePayload = [
                'fee_handling' => $validated['fee_handling'] ?? null,
                'max_tickets_per_customer' => $validated['max_tickets_per_customer'] ?? null,
                'email_subject' => $validated['email_subject'] ?? null,
                'email_body' => $validated['email_body'] ?? null,
            ];

            if (Edition::allowsPdfTicketSettings()) {
                $pdfDefaults = Event::defaultTicketPdfFields();
                $rawPdfFields = is_array($validated['ticket_pdf_fields'] ?? null) ? $validated['ticket_pdf_fields'] : [];
                $pdfFields = [];
                foreach ($pdfDefaults as $key => $defaultEnabled) {
                    if (array_key_exists($key, $rawPdfFields)) {
                        $pdfFields[$key] = filter_var($rawPdfFields[$key], FILTER_VALIDATE_BOOLEAN);
                    } else {
                        $pdfFields[$key] = (bool) $defaultEnabled;
                    }
                }

                if (Schema::hasColumn('events', 'ticket_pdf_fields')) {
                    $updatePayload['ticket_pdf_fields'] = $pdfFields;
                }
            }

            if (Edition::allowsAttendeeFormSettings()) {
                $attendeeDefaults = Event::defaultAttendeeSettings();
                $rawAttendee = is_array($request->input('attendee_settings')) ? $request->input('attendee_settings') : [];
                $rawAttendeeFields = is_array($rawAttendee['fields'] ?? null) ? $rawAttendee['fields'] : [];
                $attendeeFields = [];
                foreach ($attendeeDefaults['fields'] as $key => $defaultEnabled) {
                    if (array_key_exists($key, $rawAttendeeFields)) {
                        $attendeeFields[$key] = filter_var($rawAttendeeFields[$key], FILTER_VALIDATE_BOOLEAN);
                    } else {
                        $attendeeFields[$key] = (bool) $defaultEnabled;
                    }
                }
                $attendeeSettings = [
                    'enabled' => $request->boolean('attendee_settings.enabled'),
                    'fields' => $attendeeFields,
                ];

                if (Schema::hasColumn('events', 'attendee_settings')) {
                    $updatePayload['attendee_settings'] = $attendeeSettings;
                }
            }

            if (Edition::allowsPdfTicketSettings() && Schema::hasColumn('events', 'ticket_logo_path')) {
                if ($request->hasFile('ticket_logo')) {
                    $logoFile = $request->file('ticket_logo');
                    if ($logoFile !== null && $logoFile->isValid()) {
                        $storedLogo = PublicUploadStorage::store($logoFile, 'events/ticket-logos');
                        if ($storedLogo === null) {
                            return back()->withInput()->withErrors([
                                'ticket_logo' => __('Could not save the ticket logo. Check PHP upload limits and disk permissions.'),
                            ]);
                        }
                        if (filled($event->ticket_logo_path)) {
                            Storage::disk('uploads')->delete($event->ticket_logo_path);
                        }
                        $updatePayload['ticket_logo_path'] = $storedLogo;
                    }
                } elseif ($request->boolean('clear_ticket_logo') && filled($event->ticket_logo_path)) {
                    Storage::disk('uploads')->delete($event->ticket_logo_path);
                    $updatePayload['ticket_logo_path'] = null;
                }
            }

            $this->events->update($event, $updatePayload);
        }
        if ($request->input('wizard_action') === 'draft') {
            $this->events->update($event, ['status' => 'draft']);

            return back()->with('success', $this->wizardStepSavedMessage($wizardPanel));
        }

        if ($request->input('wizard_action') === 'publish') {
            $this->events->update($event, ['status' => 'active']);

            return redirect()
                ->route('admin.events.index')
                ->with('success', 'Advanced settings saved and event published.');
        }

        if ($wizardPanel === 'content') {
            return redirect()
                ->route('admin.events.edit.content', ['event' => $event, 'panel' => 'advanced'])
                ->with('success', 'Content saved.');
        }

        return back()->with('success', 'Advanced settings saved.');
    }

    public function publish(Event $event): RedirectResponse
    {
        $this->events->update($event, ['status' => 'active']);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event published.');
    }

    public function saveDraft(Event $event): RedirectResponse
    {
        $this->events->update($event, ['status' => 'draft']);

        return back()->with('success', 'Saved as draft.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        if ($event->cover_image_path) {
            Storage::disk('uploads')->delete($event->cover_image_path);
        }
        $this->events->delete($event);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event deleted.');
    }

    /**
     * @param  array<int|string, mixed>  $rows
     */
    private function syncEventSpeakerPivot(Event $event, array $rows): void
    {
        $ids = collect($rows)
            ->map(function (mixed $row): int {
                if (! is_array($row)) {
                    return 0;
                }

                return (int) ($row['speaker_id'] ?? 0);
            })
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $sync = [];
        foreach ($ids as $i => $speakerId) {
            $sync[$speakerId] = ['sort_order' => $i];
        }

        $event->speakers()->sync($sync);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withLocationFieldsNormalized(array $data): array
    {
        $type = $data['location_type'] ?? 'physical';

        if ($type === 'physical') {
            $data['streaming_platform'] = null;
            $data['meeting_url'] = null;
            $data['venue_city'] = null;
            $data['venue_state'] = null;
            $data['venue_postal'] = null;
            $data['venue_country'] = null;
        } elseif ($type === 'virtual') {
            $data['venue_street'] = null;
            $data['venue_city'] = null;
            $data['venue_state'] = null;
            $data['venue_postal'] = null;
            $data['venue_country'] = null;
        } else {
            $data['venue_city'] = null;
            $data['venue_state'] = null;
            $data['venue_postal'] = null;
            $data['venue_country'] = null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $safe
     * @return array<string, mixed>
     */
    private function basicPayloadFromRequest(array $safe, ?Event $existing): array
    {
        unset($safe['cover_image'], $safe['gallery_new'], $safe['gallery_remove'], $safe['category'], $safe['event_speakers'], $safe['wizard_action']);

        if (! Edition::allowsRecurringSchedule()) {
            unset(
                $safe['schedule_type'],
                $safe['recurrence_weekdays'],
                $safe['repeat_every_days'],
                $safe['recurrence_ends_on'],
                $safe['custom_schedule_dates'],
            );

            $eventTimezone = TimezoneList::normalize($safe['timezone'] ?? null);
            $startsAt = $this->combineDateTime($safe['start_date'] ?? null, $safe['start_time'] ?? null, $eventTimezone);
            $endsAt = $this->combineDateTime($safe['end_date'] ?? null, $safe['end_time'] ?? null, $eventTimezone);
            unset($safe['start_date'], $safe['start_time'], $safe['end_date'], $safe['end_time'], $safe['timezone']);

            $categoryId = $safe['event_category_id'] ?? null;
            $categoryId = $categoryId === '' || $categoryId === null ? null : (int) $categoryId;
            unset($safe['event_category_id']);
            $categoryName = $categoryId
                ? EventCategory::query()->whereKey($categoryId)->value('name')
                : null;

            $title = (string) $safe['title'];
            $slug = $existing?->slug ?? $this->makeUniqueSlug($title);
            if ($existing && $existing->title !== $title) {
                $slug = $this->makeUniqueSlug($title, $existing->id);
            }

            return array_merge($safe, [
                'slug' => $slug,
                'timezone' => $eventTimezone,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $existing?->status ?? 'draft',
                'organizer_id' => $safe['organizer_id'] ?? null,
                'event_category_id' => $categoryId,
                'category' => $categoryName,
                'schedule_type' => 'single',
                'recurrence_weekdays' => null,
                'repeat_every_days' => null,
                'custom_schedule_dates' => null,
                'recurrence_ends_on' => null,
            ]);
        }

        $scheduleType = $safe['schedule_type'] ?? 'single';
        if (! in_array($scheduleType, ['single', 'recurring', 'custom_interval'], true)) {
            $scheduleType = 'single';
        }
        $weekdays = null;
        $repeatEvery = null;
        $recurrenceEndsOn = null;
        $customScheduleDates = null;
        if ($scheduleType === 'recurring') {
            $raw = $safe['recurrence_weekdays'] ?? [];
            if (! is_array($raw)) {
                $raw = [];
            }
            $weekdays = array_values(array_unique(array_map(static fn ($v) => (int) $v, $raw)));
            $weekdays = array_values(array_filter($weekdays, static fn ($d) => $d >= 0 && $d <= 6));
            sort($weekdays, SORT_NUMERIC);
            if (count($weekdays) === 0) {
                $weekdays = null;
            }
            $recurrenceEndsOn = $this->parseDateOnly(isset($safe['recurrence_ends_on']) ? (string) $safe['recurrence_ends_on'] : null);
        } elseif ($scheduleType === 'custom_interval') {
            $customScheduleDates = $this->normalizedCustomScheduleDates($safe['custom_schedule_dates'] ?? []);
            if (count($customScheduleDates) > 0) {
                $first = $customScheduleDates[0];
                $safe['start_date'] = $first;
                $safe['end_date'] = $first;
                $recurrenceEndsOn = $this->parseDateOnly($customScheduleDates[array_key_last($customScheduleDates)]);
            }
        } else {
            $scheduleType = 'single';
        }

        unset(
            $safe['schedule_type'],
            $safe['recurrence_weekdays'],
            $safe['repeat_every_days'],
            $safe['recurrence_ends_on'],
            $safe['custom_schedule_dates'],
        );

        $eventTimezone = TimezoneList::normalize($safe['timezone'] ?? null);
        $startsAt = $this->combineDateTime($safe['start_date'] ?? null, $safe['start_time'] ?? null, $eventTimezone);
        $endsAt = $this->combineDateTime($safe['end_date'] ?? null, $safe['end_time'] ?? null, $eventTimezone);
        unset($safe['start_date'], $safe['start_time'], $safe['end_date'], $safe['end_time'], $safe['timezone']);

        $categoryId = $safe['event_category_id'] ?? null;
        $categoryId = $categoryId === '' || $categoryId === null ? null : (int) $categoryId;
        unset($safe['event_category_id']);
        $categoryName = $categoryId
            ? EventCategory::query()->whereKey($categoryId)->value('name')
            : null;

        $title = (string) $safe['title'];
        $slug = $existing?->slug ?? $this->makeUniqueSlug($title);
        if ($existing && $existing->title !== $title) {
            $slug = $this->makeUniqueSlug($title, $existing->id);
        }

        return array_merge($safe, [
            'slug' => $slug,
            'timezone' => $eventTimezone,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $existing?->status ?? 'draft',
            'organizer_id' => $safe['organizer_id'] ?? null,
            'event_category_id' => $categoryId,
            'category' => $categoryName,
            'schedule_type' => $scheduleType,
            'recurrence_weekdays' => $scheduleType === 'recurring' ? $weekdays : null,
            'repeat_every_days' => null,
            'custom_schedule_dates' => $scheduleType === 'custom_interval' ? $customScheduleDates : null,
            'recurrence_ends_on' => in_array($scheduleType, ['recurring', 'custom_interval'], true) ? $recurrenceEndsOn : null,
        ]);
    }

    private function combineDateTime(?string $date, ?string $time, string $timezone): ?Carbon
    {
        return EventDatetime::parseWallClock($date, $time, $timezone);
    }

    private function parseDateOnly(?string $date): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        return Carbon::parse($date)->startOfDay();
    }

    /**
     * @return list<string>
     */
    private function normalizedCustomScheduleDates(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $d) {
            if ($d === null || $d === '') {
                continue;
            }
            try {
                $out[] = Carbon::parse((string) $d)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }
        $out = array_values(array_unique($out));
        sort($out);

        return $out;
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

    /**
     * @param  list<array<string, mixed>>|null  $tickets
     * @param  array<string, array{early_bird_price: mixed, early_bird_ends_at: mixed, sales_start: mixed}>|null  $preserveEarlyBirdByName
     * @return array{rows: list<array{name: string, price: float, quantity: int, sales_start: ?string, sales_end: ?string, early_bird_price: ?float, early_bird_ends_at: ?string}>, capacity: int}
     */
    private function normalizedTicketsPayload(
        ?array $tickets,
        bool $globalQuantityEnabled,
        int $globalQuantity,
        ?array $preserveEarlyBirdByName = null,
    ): array {
        if (! is_array($tickets) || count($tickets) === 0) {
            return ['rows' => [], 'capacity' => $globalQuantityEnabled ? max(0, $globalQuantity) : 0];
        }

        $out = [];
        $cap = 0;
        foreach ($tickets as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $qty = $globalQuantityEnabled
                ? 0
                : max(0, (int) ($row['quantity'] ?? 0));
            if (! $globalQuantityEnabled) {
                $cap += $qty;
            }
            $ebRaw = $row['early_bird_price'] ?? null;
            $earlyBirdPrice = ($ebRaw === null || $ebRaw === '') ? null : round((float) $ebRaw, 2);
            $earlyBirdEnds = $this->parseTicketDate($row['early_bird_ends_at'] ?? null);
            $salesStart = $this->parseTicketDate($row['sales_start'] ?? null);
            if ($preserveEarlyBirdByName !== null) {
                $preserved = $preserveEarlyBirdByName[$name] ?? null;
                if (is_array($preserved)) {
                    $earlyBirdPrice = $preserved['early_bird_price'];
                    $earlyBirdEnds = $this->parseTicketDate($preserved['early_bird_ends_at'] ?? null);
                    $salesStart = $this->parseTicketDate($preserved['sales_start'] ?? null);
                } else {
                    $earlyBirdPrice = null;
                    $earlyBirdEnds = null;
                    $salesStart = null;
                }
            } elseif ($earlyBirdPrice === null) {
                $earlyBirdEnds = null;
            }
            $out[] = [
                'name' => $name,
                'price' => round((float) ($row['price'] ?? 0), 2),
                'quantity' => $qty,
                'sales_start' => $salesStart,
                'sales_end' => $this->parseTicketDate($row['sales_end'] ?? null),
                'early_bird_price' => $earlyBirdPrice,
                'early_bird_ends_at' => $earlyBirdEnds,
            ];
        }

        return [
            'rows' => $out,
            'capacity' => $globalQuantityEnabled ? max(0, $globalQuantity) : $cap,
        ];
    }

    private function parseTicketDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }
        try {
            return Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<array<string, mixed>>|null  $services
     * @return list<array{name: string, price: float, quantity: int}>
     */
    private function normalizedAdditionalServicesPayload(?array $services): array
    {
        if (! is_array($services) || count($services) === 0) {
            return [];
        }

        $out = [];
        foreach ($services as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'name' => $name,
                'price' => round((float) ($row['price'] ?? 0), 2),
                'quantity' => max(0, (int) ($row['quantity'] ?? 0)),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>|null  $faqs
     * @return array<int, array{question: string, answer: string}>|null
     */
    private function normalizedFaqs(?array $faqs): ?array
    {
        if (! is_array($faqs)) {
            return null;
        }

        $out = [];
        foreach ($faqs as $row) {
            $q = trim((string) ($row['question'] ?? ''));
            $a = trim((string) ($row['answer'] ?? ''));
            if ($q === '' && $a === '') {
                continue;
            }
            $out[] = ['question' => $q, 'answer' => $a];
        }

        return count($out) > 0 ? $out : null;
    }

    /**
     * @param  list<array<string, mixed>>|null  $timeline
     * @return array<int, array{time_label: string, title: string}>|null
     */
    private function normalizedTimelineItems(?array $timeline): ?array
    {
        if (! is_array($timeline)) {
            return null;
        }

        $out = [];
        foreach ($timeline as $row) {
            $time = trim((string) ($row['time_label'] ?? ''));
            $title = trim((string) ($row['title'] ?? ''));
            if ($time === '' && $title === '') {
                continue;
            }
            if ($title === '') {
                continue;
            }
            $out[] = ['time_label' => $time, 'title' => $title];
        }

        return count($out) > 0 ? $out : null;
    }

    /**
     * @param  list<array{id: ?int, code: string, discount_type: string, discount_value: float, max_uses: ?int, valid_from: ?string, valid_until: ?string, is_active: bool}>  $rows
     */
    private function syncEventCoupons(Event $event, array $rows): void
    {
        if (count($rows) === 0) {
            EventCoupon::query()->where('event_id', $event->id)->delete();

            return;
        }

        $keepIds = [];
        foreach ($rows as $i => $row) {
            if (! empty($row['id'])) {
                $coupon = EventCoupon::query()->where('event_id', $event->id)->whereKey($row['id'])->first();
                if ($coupon) {
                    $coupon->update([
                        'sort_order' => $i,
                        'code' => $row['code'],
                        'discount_type' => $row['discount_type'],
                        'discount_value' => $row['discount_value'],
                        'max_uses' => $row['max_uses'],
                        'valid_from' => $row['valid_from'],
                        'valid_until' => $row['valid_until'],
                        'is_active' => $row['is_active'],
                    ]);
                    $keepIds[] = $coupon->id;

                    continue;
                }
            }

            $coupon = EventCoupon::query()->create([
                'event_id' => $event->id,
                'sort_order' => $i,
                'code' => $row['code'],
                'discount_type' => $row['discount_type'],
                'discount_value' => $row['discount_value'],
                'max_uses' => $row['max_uses'],
                'valid_from' => $row['valid_from'],
                'valid_until' => $row['valid_until'],
                'is_active' => $row['is_active'],
                'uses_count' => 0,
            ]);
            $keepIds[] = $coupon->id;
        }

        EventCoupon::query()->where('event_id', $event->id)->whereNotIn('id', $keepIds)->delete();
    }

    /**
     * @param  list<array<string, mixed>>|null  $coupons
     * @return list<array{id: ?int, code: string, discount_type: string, discount_value: float, max_uses: ?int, valid_from: ?string, valid_until: ?string, is_active: bool}>
     */
    private function normalizedCouponsPayload(?array $coupons): array
    {
        if (! is_array($coupons) || count($coupons) === 0) {
            return [];
        }

        $out = [];
        foreach ($coupons as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            if ($code === '') {
                continue;
            }
            $type = (($row['discount_type'] ?? '') === 'fixed') ? 'fixed' : 'percent';
            $val = round((float) ($row['discount_value'] ?? 0), 2);
            if ($type === 'percent' && $val > 100) {
                $val = 100;
            }
            $id = isset($row['id']) && $row['id'] !== '' && $row['id'] !== null ? (int) $row['id'] : null;
            $maxUses = isset($row['max_uses']) && $row['max_uses'] !== '' && $row['max_uses'] !== null ? max(1, (int) $row['max_uses']) : null;

            $out[] = [
                'id' => $id,
                'code' => $code,
                'discount_type' => $type,
                'discount_value' => $val,
                'max_uses' => $maxUses,
                'valid_from' => $this->parseTicketDate($row['valid_from'] ?? null),
                'valid_until' => $this->parseTicketDate($row['valid_until'] ?? null),
                'is_active' => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $out;
    }

    public function bookings(Request $request, Event $event): View
    {
        return view('admin.events.bookings', [
            'activeNav' => 'events',
            'event' => $event,
            'bookingsDataUrl' => route('admin.events.bookings.data', $event),
            'initialSearch' => is_string($request->query('q')) ? $request->query('q') : '',
        ]);
    }

    public function bookingsData(Request $request, Event $event): JsonResponse
    {
        $query = $event->bookings();

        $search = $request->query('q');
        if (is_string($search) && $search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('attendee_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        $orderGroups = $query
            ->selectRaw('MIN(id) as representative_id, order_group_id, MAX(created_at) as booked_at')
            ->groupBy('order_group_id')
            ->orderByDesc('booked_at')
            ->paginate(8)
            ->withQueryString();

        $groupIds = $orderGroups->getCollection()
            ->pluck('order_group_id')
            ->filter(static fn ($v) => is_string($v) && $v !== '')
            ->values();

        $rowsByGroup = $event->bookings()
            ->with(['ticket'])
            ->whereIn('order_group_id', $groupIds)
            ->get()
            ->groupBy('order_group_id');

        $bookings = $orderGroups->getCollection()->map(static function ($g) use ($rowsByGroup) {
            $gid = is_string($g->order_group_id ?? null) ? $g->order_group_id : '';
            /** @var \Illuminate\Support\Collection<int, EventBooking> $rows */
            $rows = $rowsByGroup->get($gid, collect());
            /** @var EventBooking|null $first */
            $first = $rows->sortBy('id')->first();
            if (! $first instanceof EventBooking) {
                return null;
            }

            $offlinePaymentLabel = null;
            if (in_array((string) $first->offline_payment_method, ['cash', 'bank_transfer'], true)) {
                $offlinePaymentLabel = $first->offline_payment_method === 'cash' ? 'Cash' : 'Bank';
            }

            $ticketSummary = $rows
                ->groupBy(static fn (EventBooking $b): string => (string) ($b->ticket?->name ?? 'Ticket'))
                ->map(static fn ($items, string $name): string => $name.' ×'.count($items))
                ->values()
                ->implode(', ');

            $statuses = $rows->pluck('status')->filter()->unique()->values();
            $statusLabel = $statuses->count() === 1 ? (string) $statuses->first() : 'mixed';
            if ($rows->count() > 1) {
                $statusLabel .= ' ('.$rows->count().' tickets)';
            }

            return [
                'id' => $first->id,
                'order_group_id' => $gid,
                'attendee_name' => $first->attendee_name,
                'email' => $first->email,
                'phone' => $first->phone,
                'ticket_name' => $ticketSummary,
                'occurrence_date_label' => $first->occurrence_date?->format('D, M j, Y'),
                'status' => $statusLabel,
                'offline_payment_label' => $offlinePaymentLabel,
                'offline_payment_reference' => $first->offline_payment_reference,
                'checked_in_at_label' => $first->checked_in_at?->format('M j, g:i A'),
                'booked_at_label' => $first->created_at?->format('M j, Y g:i A'),
                'items' => $rows->map(fn ($r) => [
                    'id' => $r->id,
                    'attendee_name' => $r->attendee_name,
                    'email' => $r->email,
                    'phone' => $r->phone,
                    'ticket_name' => $r->ticket?->name ?? 'Ticket',
                    'seat_label' => $r->seatDisplayLabel(),
                    'status' => $r->status,
                    'occurrence_date_label' => $r->occurrence_date?->format('D, M j, Y'),
                    'checked_in_at_label' => $r->checked_in_at?->format('M j, g:i A'),
                    'notes' => $r->notes,
                ])->values(),
            ];
        })->filter()->values();

        return response()->json([
            'bookings' => $bookings,
            'pagination' => [
                'current_page' => $orderGroups->currentPage(),
                'last_page' => $orderGroups->lastPage(),
                'per_page' => $orderGroups->perPage(),
                'total' => $orderGroups->total(),
                'from' => $orderGroups->firstItem(),
                'to' => $orderGroups->lastItem(),
            ],
            'prev_page_url' => $orderGroups->previousPageUrl(),
            'next_page_url' => $orderGroups->nextPageUrl(),
        ]);
    }

    public function bookingDetails(Event $event, string $orderGroupId): JsonResponse
    {
        $bookings = $event->bookings()
            ->with(['ticket'])
            ->where('order_group_id', $orderGroupId)
            ->get();

        if ($bookings->isEmpty()) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $first = $bookings->first();

        $items = $bookings->map(fn ($b) => [
            'id' => $b->id,
            'attendee_name' => $b->attendee_name,
            'email' => $b->email,
            'phone' => $b->phone,
            'ticket_name' => $b->ticket?->name ?? 'Ticket',
            'seat_label' => $b->seatDisplayLabel(),
            'status' => $b->status,
            'checked_in_at' => $b->checked_in_at?->format('M j, g:i A'),
        ]);

        return response()->json([
            'order_group_id' => $orderGroupId,
            'attendee_name' => $first->attendee_name,
            'email' => $first->email,
            'phone' => $first->phone,
            'status' => $first->status,
            'is_checked_in' => $first->checked_in_at !== null,
            'notes' => $first->notes,
            'items' => $items,
        ]);
    }

    public function updateBookingGroup(Request $request, Event $event, string $orderGroupId): JsonResponse
    {
        $bookings = $event->bookings()
            ->where('order_group_id', $orderGroupId)
            ->get();

        if ($bookings->isEmpty()) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $validated = $request->validate([
            'attendee_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:40',
            'status' => 'required|string|max:40',
            'is_checked_in' => 'boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        $updateData = [
            'attendee_name' => $validated['attendee_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ];

        $isCheckedIn = (bool) ($validated['is_checked_in'] ?? false);

        foreach ($bookings as $booking) {
            $data = $updateData;
            if ($isCheckedIn && $booking->checked_in_at === null) {
                $data['checked_in_at'] = now();
            } elseif (! $isCheckedIn) {
                $data['checked_in_at'] = null;
            }
            $booking->update($data);
        }

        return response()->json(['message' => 'Booking updated successfully.']);
    }

    private function wizardStepSavedMessage(?string $contentPanel = null): string
    {
        $route = request()->route()?->getName();

        if ($route === 'admin.events.update.content') {
            return ($contentPanel === 'advanced' ? 'Advanced' : 'Content').' saved.';
        }

        return match ($route) {
            'admin.events.update' => 'Basic Info saved.',
            'admin.events.update.media' => 'Media saved.',
            'admin.events.update.location' => 'Location saved.',
            'admin.events.update.tickets' => 'Ticketing saved.',
            'admin.events.update.speakers' => 'Speakers saved.',
            default => 'Saved.',
        };
    }
}
