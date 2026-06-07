<?php

namespace App\Http\Controllers;

use App\Models\EventBooking;
use App\Models\SiteSetting;
use App\Support\PublicFrontendTheme;
use App\Support\TimezoneList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class FrontendAccountController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function siteContext(Request $request): array
    {
        if (!Schema::hasTable('site_settings')) {
            $setting = new SiteSetting([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
            ]);
        } else {
            $setting = SiteSetting::instance();
        }

        $extras = PublicFrontendTheme::publicPageExtras();

        return [
            'siteSetting' => $setting,
            'siteName' => $setting->site_name ?: config('app.name', 'Event Manager'),
            'siteLogoUrl' => PublicFrontendTheme::resolvePublicLogoUrl($setting),
            'contactEmail' => $extras['contactEmail'],
            'contactPhone' => $extras['contactPhone'],
            'heroImageUrl' => $extras['heroImageUrl'],
            'accountLayout' => PublicFrontendTheme::isClassicFamily()
                ? 'public.classic.layouts.app'
                : 'public.layouts.frontend-default',
        ];
    }

    public function index(Request $request): View
    {
        $base = EventBooking::query()
            ->where('user_id', $request->user()->id);

        $bookings = (clone $base)
            ->selectRaw('MIN(id) as representative_id, order_group_id, MAX(created_at) as booked_at')
            ->groupBy('order_group_id')
            ->orderByDesc('booked_at')
            ->paginate(12);

        $groupIds = $bookings->getCollection()
            ->pluck('order_group_id')
            ->filter(static fn($v) => is_string($v) && $v !== '')
            ->values();

        $rowsByGroup = EventBooking::query()
            ->where('user_id', $request->user()->id)
            ->with(['event', 'ticket'])
            ->whereIn('order_group_id', $groupIds)
            ->get()
            ->groupBy('order_group_id');

        $bookings->setCollection(
            $bookings->getCollection()->map(static function ($g) use ($rowsByGroup) {
                $gid = is_string($g->order_group_id ?? null) ? $g->order_group_id : '';
                /** @var \Illuminate\Support\Collection<int, EventBooking> $rows */
                $rows = $rowsByGroup->get($gid, collect());
                /** @var EventBooking|null $primary */
                $primary = $rows->sortBy('id')->first();
                if (!$primary instanceof EventBooking) {
                    return null;
                }

                $ticketSummary = $rows
                    ->groupBy(static fn(EventBooking $b): string => (string) ($b->ticket?->name ?? 'Ticket'))
                    ->map(static fn($items, string $name): string => $name . ' ×' . count($items))
                    ->values()
                    ->implode(', ');

                $statuses = $rows->pluck('status')->filter()->unique()->values();
                $statusLabel = $statuses->count() === 1 ? (string) $statuses->first() : 'mixed';
                $ticketRows = $rows->map(static function (EventBooking $row): object {
                    return (object) [
                        'id' => $row->id,
                        'ticket_name' => (string) ($row->ticket?->name ?? 'Ticket'),
                        'seat_label' => $row->seatDisplayLabel(),
                        'attendee_name' => (string) ($row->attendee_name ?? ''),
                        'occurrence_date' => $row->occurrence_date,
                        'status' => (string) $row->status,
                        'created_at' => $row->created_at,
                    ];
                })->values();

                return (object) [
                    'id' => $primary->id,
                    'order_group_id' => $gid,
                    'event' => $primary->event,
                    'ticket_summary' => $ticketSummary,
                    'tickets_count' => $rows->count(),
                    'occurrence_date' => $primary->occurrence_date,
                    'status_label' => $statusLabel,
                    'primary_booking' => $primary,
                    'ticket_rows' => $ticketRows,
                ];
            })->filter()->values()
        );

        return view(
            PublicFrontendTheme::isClassicFamily()
            ? 'public.classic.account.index'
            : 'public.account.index',
            array_merge($this->siteContext($request), [
                'bookings' => $bookings,
            ])
        );
    }

    public function order(Request $request, string $orderGroupId): View
    {
        $tickets = EventBooking::query()
            ->where('user_id', $request->user()->id)
            ->where('order_group_id', $orderGroupId)
            ->with(['event', 'ticket'])
            ->orderBy('id')
            ->get();

        if ($tickets->isEmpty()) {
            abort(404);
        }

        $primary = $tickets->first();
        $event = $primary?->event;

        return view(
            PublicFrontendTheme::isClassicFamily()
            ? 'public.classic.account.order'
            : 'public.account.order',
            array_merge($this->siteContext($request), [
                'orderGroupId' => $orderGroupId,
                'tickets' => $tickets,
                'event' => $event,
                'primaryBooking' => $primary,
            ])
        );
    }

    public function profile(Request $request): View
    {
        return view(
            PublicFrontendTheme::isClassicFamily()
            ? 'public.classic.account.profile'
            : 'public.account.profile',
            array_merge($this->siteContext($request), [
                'user' => $request->user(),
            ])
        );
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'timezone' => [
                'nullable',
                'string',
                'max:64',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && $value !== '' && ! TimezoneList::isValid((string) $value)) {
                        $fail('Please choose a valid timezone.');
                    }
                },
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password:web'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->timezone = filled($data['timezone'] ?? null) ? $data['timezone'] : null;

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('account.profile')->with('status', 'Profile updated.');
    }
}
