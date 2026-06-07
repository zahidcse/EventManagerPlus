<?php

namespace App\Support\Admin;

final class AdminModules
{
    public const DASHBOARD = 'dashboard';

    public const EVENTS = 'events';

    public const EVENT_CATEGORIES = 'event_categories';

    public const SPEAKERS = 'speakers';

    public const EVENT_ASSISTANT = 'event_assistant';

    public const ORGANIZERS = 'organizers';

    public const REPORTS = 'reports';

    public const REPORT_AI = 'report_ai';

    public const PAGES = 'pages';

    public const BLOG = 'blog';

    public const SETTINGS = 'settings';

    public const STAFF = 'staff';

    /**
     * @return array<string, array{label: string, description: string, group: string}>
     */
    public static function definitions(): array
    {
        return [
            self::DASHBOARD => [
                'label' => 'Dashboard',
                'description' => 'View overview and metrics',
                'group' => 'General',
            ],
            self::EVENTS => [
                'label' => 'Events',
                'description' => 'Create and manage events, bookings, tickets',
                'group' => 'Events',
            ],
            self::EVENT_CATEGORIES => [
                'label' => 'Event categories',
                'description' => 'Manage event category taxonomy',
                'group' => 'Events',
            ],
            self::SPEAKERS => [
                'label' => 'Speakers',
                'description' => 'Manage speaker profiles',
                'group' => 'Events',
            ],
            self::EVENT_ASSISTANT => [
                'label' => 'AI assistance',
                'description' => 'Use the AI event assistant',
                'group' => 'Events',
            ],
            self::ORGANIZERS => [
                'label' => 'Organizers',
                'description' => 'Manage event partner organizations',
                'group' => 'Events',
            ],
            self::REPORTS => [
                'label' => 'Classic reports',
                'description' => 'View and export booking reports',
                'group' => 'Reports',
            ],
            self::REPORT_AI => [
                'label' => 'AI reports',
                'description' => 'Query data with AI-powered reports',
                'group' => 'Reports',
            ],
            self::PAGES => [
                'label' => 'Pages',
                'description' => 'Manage CMS pages',
                'group' => 'Content',
            ],
            self::BLOG => [
                'label' => 'Blog',
                'description' => 'Manage blog posts',
                'group' => 'Content',
            ],
            self::SETTINGS => [
                'label' => 'Settings',
                'description' => 'Site, email, payment, and admin settings',
                'group' => 'System',
            ],
            self::STAFF => [
                'label' => 'Team & roles',
                'description' => 'Manage staff accounts and role permissions',
                'group' => 'System',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Routes that any authenticated admin may access (no module check).
     *
     * @return list<string>
     */
    public static function unrestrictedRouteNames(): array
    {
        return [
            'admin.logout',
            'admin.profile.edit',
            'admin.profile.update',
            'admin.notifications.bookings.dismiss',
            'admin.check-in.show',
            'admin.check-in.store',
            'admin.editor.upload',
        ];
    }

    /**
     * Resolve the module key required for an admin route name.
     */
    public static function moduleForRoute(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === 'admin.dashboard') {
            return self::DASHBOARD;
        }

        if (str_starts_with($routeName, 'admin.staff.') || str_starts_with($routeName, 'admin.roles.')) {
            return self::STAFF;
        }

        if (str_starts_with($routeName, 'admin.events.') || $routeName === 'admin.events.index') {
            return self::EVENTS;
        }

        if (str_starts_with($routeName, 'admin.event-categories.')) {
            return self::EVENT_CATEGORIES;
        }

        if (str_starts_with($routeName, 'admin.speakers.')) {
            return self::SPEAKERS;
        }

        if (str_starts_with($routeName, 'admin.event-assistant.')) {
            return self::EVENT_ASSISTANT;
        }

        if (str_starts_with($routeName, 'admin.organizers.')) {
            return self::ORGANIZERS;
        }

        if (str_starts_with($routeName, 'admin.reports.')) {
            return self::REPORTS;
        }

        if (str_starts_with($routeName, 'admin.report-ai.')) {
            return self::REPORT_AI;
        }

        if (str_starts_with($routeName, 'admin.pages.')) {
            return self::PAGES;
        }

        if (str_starts_with($routeName, 'admin.blog.')) {
            return self::BLOG;
        }

        if (str_starts_with($routeName, 'admin.settings.')) {
            return self::SETTINGS;
        }

        return null;
    }

    /**
     * First admin route the user can access (fallback after login).
     */
    public static function defaultLandingRouteName(\App\Models\User $user): string
    {
        $order = [
            self::DASHBOARD => 'admin.dashboard',
            self::EVENTS => 'admin.events.index',
            self::EVENT_CATEGORIES => 'admin.event-categories.index',
            self::SPEAKERS => 'admin.speakers.index',
            self::ORGANIZERS => 'admin.organizers.index',
            self::REPORTS => 'admin.reports.index',
            self::PAGES => 'admin.pages.index',
            self::BLOG => 'admin.blog.index',
            self::SETTINGS => 'admin.settings.index',
            self::STAFF => 'admin.staff.index',
        ];

        foreach ($order as $module => $routeName) {
            if ($user->canAccessAdminModule($module)) {
                return $routeName;
            }
        }

        return 'admin.profile.edit';
    }
}
