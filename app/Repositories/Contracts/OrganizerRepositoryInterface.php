<?php

namespace App\Repositories\Contracts;

use App\Models\Organizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrganizerRepositoryInterface
{
    public function paginateFiltered(int $perPage = 15, ?string $status = null, ?string $search = null): LengthAwarePaginator;

    public function totalCount(): int;

    public function activeCount(): int;

    /**
     * @return Collection<int, Organizer>
     */
    public function topByEventsCount(int $limit = 2): Collection;

    public function find(int $id): ?Organizer;

    public function create(array $data): Organizer;

    public function update(Organizer $organizer, array $data): Organizer;

    public function delete(Organizer $organizer): bool;
}
