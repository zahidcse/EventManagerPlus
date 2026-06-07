<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface EventRepositoryInterface
{
    public function paginateFiltered(
        int $perPage = 15,
        ?string $status = null,
        ?string $timeRange = null,
        ?string $search = null,
    ): LengthAwarePaginator;

    /**
     * @return array{
     *     total: int,
     *     active: int,
     *     active_now: int,
     *     registrations_sum: int,
     *     drafts: int,
     *     total_booking_cents: int,
     *     booking_currency: string
     * }
     */
    public function dashboardStats(): array;

    /**
     * @return array{
     *     stats: array{
     *         total: int,
     *         active: int,
     *         active_now: int,
     *         registrations_sum: int,
     *         drafts: int,
     *         total_booking_cents: int,
     *         booking_currency: string
     *     },
     *     organizers_count: int,
     *     events_this_month: int,
     *     capacity_used_percent: int,
     *     total_capacity: int,
     *     chart_days: list<array{label: string, count: int, date: string, height_percent: int}>,
     *     revenue: array<string, mixed>,
     *     upcoming_events: Collection<int, Event>,
     *     recent_activity: list<array{title: string, kind: string, at: Carbon}>,
     *     recent_events: Collection<int, Event>
     * }
     */
    public function dashboardOverview(): array;

    public function find(int $id): ?Event;

    public function create(array $data): Event;

    public function update(Event $event, array $data): Event;

    public function delete(Event $event): bool;
}
