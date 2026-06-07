<?php

namespace App\Services;

use App\Models\EventBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class AdminBookingNotificationService
{
    /** Only surface bookings recent enough to avoid flooding admins on first use. */
    private const RECENT_DAYS = 365;

    /** @return Builder<EventBooking>|null */
    private function unreadBaseQuery(User $admin): ?Builder
    {
        try {
            if (! Schema::hasTable('admin_booking_notification_dismissals') || ! Schema::hasTable('event_bookings')) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return EventBooking::query()
            ->where('event_bookings.created_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->whereDoesntHave('adminNotificationDismissals', static fn ($q) => $q->where('user_id', $admin->id));
    }

    public function unreadCount(User $admin): int
    {
        $q = $this->unreadBaseQuery($admin);

        return $q === null ? 0 : (clone $q)->count();
    }

    /**
     * @return Collection<int, EventBooking>
     */
    public function recentUnread(User $admin, int $limit = 15): Collection
    {
        $q = $this->unreadBaseQuery($admin);
        if ($q === null) {
            return collect();
        }

        return (clone $q)
            ->with(['event:id,title'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
