<?php

namespace App\Repositories;

use App\Models\Organizer;
use App\Repositories\Contracts\OrganizerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrganizerRepository implements OrganizerRepositoryInterface
{
    public function paginateFiltered(int $perPage = 15, ?string $status = null, ?string $search = null): LengthAwarePaginator
    {
        $query = Organizer::query()->with('adminRole')->withCount('events')->orderByDesc('created_at');

        if ($status === 'active' || $status === 'inactive') {
            $query->where('status', $status);
        }

        if ($search !== null && $search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function totalCount(): int
    {
        return Organizer::query()->count();
    }

    public function activeCount(): int
    {
        return Organizer::query()->where('status', 'active')->count();
    }

    public function topByEventsCount(int $limit = 2): Collection
    {
        return Organizer::query()
            ->withCount('events')
            ->orderByDesc('events_count')
            ->limit($limit)
            ->get();
    }

    public function find(int $id): ?Organizer
    {
        return Organizer::query()->find($id);
    }

    public function create(array $data): Organizer
    {
        return Organizer::query()->create($data);
    }

    public function update(Organizer $organizer, array $data): Organizer
    {
        $organizer->update($data);

        return $organizer->refresh();
    }

    public function delete(Organizer $organizer): bool
    {
        return (bool) $organizer->delete();
    }
}
