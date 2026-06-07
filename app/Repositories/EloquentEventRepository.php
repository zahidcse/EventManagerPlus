<?php

namespace App\Repositories;

use App\Models\Event;
use App\Models\Organizer;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Support\AdminDashboardRevenue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentEventRepository implements EventRepositoryInterface
{
    public function paginateFiltered(
        int $perPage = 15,
        ?string $status = null,
        ?string $timeRange = null,
        ?string $search = null,
    ): LengthAwarePaginator {
        $query = Event::query()->with(['organizer', 'eventCategory'])->orderByDesc('starts_at')->orderByDesc('created_at');

        if (in_array($status, ['draft', 'active', 'completed'], true)) {
            $query->where('status', $status);
        }

        $now = Carbon::now();
        match ($timeRange) {
            'this_week' => $query->whereBetween('starts_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]),
            'this_month' => $query->whereBetween('starts_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]),
            'next_3_months' => $query->whereBetween('starts_at', [$now->copy()->startOfDay(), $now->copy()->addMonths(3)->endOfDay()]),
            default => null,
        };

        if ($search !== null && $search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhereHas('eventCategory', function ($cq) use ($term) {
                        $cq->where('name', 'like', $term);
                    })
                    ->orWhere('venue_city', 'like', $term)
                    ->orWhereHas('faqs', function ($fq) use ($term) {
                        $fq->where('question', 'like', $term)
                            ->orWhere('answer', 'like', $term);
                    })
                    ->orWhereHas('tickets', function ($tq) use ($term) {
                        $tq->where('name', 'like', $term);
                    })
                    ->orWhereHas('additionalServices', function ($sq) use ($term) {
                        $sq->where('name', 'like', $term);
                    });
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function dashboardStats(): array
    {
        $revenue = AdminDashboardRevenue::snapshot();

        return [
            'total' => Event::query()->count(),
            'active' => Event::query()->where('status', 'active')->count(),
            'active_now' => $this->countEventsInProgressNow(),
            'registrations_sum' => (int) Event::query()->sum('registrations_count'),
            'drafts' => Event::query()->where('status', 'draft')->count(),
            'total_booking_cents' => (int) ($revenue['total_cents'] ?? 0),
            'booking_currency' => (string) ($revenue['currency'] ?? 'usd'),
        ];
    }

    public function dashboardOverview(): array
    {
        $now = Carbon::now();
        $stats = $this->dashboardStats();

        $organizersCount = Organizer::query()->count();

        $eventsThisMonth = Event::query()
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->count();

        $totalCapacity = (int) Event::query()->sum('capacity');
        $totalReg = $stats['registrations_sum'];
        $capacityUsedPercent = $totalCapacity > 0
            ? (int) min(100, round(($totalReg / $totalCapacity) * 100))
            : 0;

        $chartStart = $now->copy()->subDays(6)->startOfDay();
        $chartDays = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $chartStart->copy()->addDays($i);
            $count = Event::query()->whereDate('created_at', $day->toDateString())->count();
            $chartDays[] = [
                'label' => $day->format('D'),
                'count' => $count,
                'date' => $day->toDateString(),
            ];
        }
        $maxChart = max(1, ...array_column($chartDays, 'count'));
        foreach ($chartDays as $idx => $row) {
            $chartDays[$idx]['height_percent'] = (int) round(($row['count'] / $maxChart) * 100);
        }

        $upcomingEvents = Event::query()
            ->with('organizer')
            ->whereNotNull('starts_at')
            ->upcomingForPublicListing($now)
            ->orderBy('starts_at')
            ->limit(6)
            ->get();

        $recentActivity = Event::query()
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->map(function (Event $e) {
                $created = $e->created_at;
                $updated = $e->updated_at;
                $kind = ($created && $updated && abs($created->diffInSeconds($updated)) <= 2)
                    ? 'created'
                    : 'updated';

                return [
                    'title' => $e->title,
                    'kind' => $kind,
                    'at' => $updated,
                ];
            })
            ->all();

        $recentEvents = Event::query()
            ->with(['organizer', 'eventCategory'])
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        return [
            'stats' => $stats,
            'organizers_count' => $organizersCount,
            'events_this_month' => $eventsThisMonth,
            'capacity_used_percent' => $capacityUsedPercent,
            'total_capacity' => $totalCapacity,
            'chart_days' => $chartDays,
            'revenue' => AdminDashboardRevenue::snapshot(),
            'upcoming_events' => $upcomingEvents,
            'recent_activity' => $recentActivity,
            'recent_events' => $recentEvents,
        ];
    }

    public function find(int $id): ?Event
    {
        return Event::query()->find($id);
    }

    public function create(array $data): Event
    {
        return DB::transaction(function () use ($data) {
            $event = Event::query()->create($data);
            $this->refreshOrganizerEventsCount($event->organizer_id);

            return $event;
        });
    }

    public function update(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            $previousOrganizerId = $event->organizer_id;
            $event->update($data);
            $event->refresh();
            $this->refreshOrganizerEventsCount($previousOrganizerId);
            $this->refreshOrganizerEventsCount($event->organizer_id);

            return $event->refresh();
        });
    }

    public function delete(Event $event): bool
    {
        return DB::transaction(function () use ($event) {
            $organizerId = $event->organizer_id;
            $deleted = (bool) $event->delete();
            $this->refreshOrganizerEventsCount($organizerId);

            return $deleted;
        });
    }

    private function refreshOrganizerEventsCount(?int $organizerId): void
    {
        if ($organizerId === null) {
            return;
        }

        $count = Event::query()->where('organizer_id', $organizerId)->count();
        Organizer::query()->whereKey($organizerId)->update(['events_count' => $count]);
    }

    private function countEventsInProgressNow(): int
    {
        return Event::query()
            ->where('status', 'active')
            ->get()
            ->filter(static fn (Event $event) => $event->isInProgressNow())
            ->count();
    }
}
