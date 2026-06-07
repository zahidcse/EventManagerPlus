<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Support\Admin\AdminModules;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        AdminRole::query()->updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Administrator',
                'audience' => AdminRole::AUDIENCE_STAFF,
                'description' => 'Full access to every admin module',
                'permissions' => AdminModules::keys(),
                'is_super' => true,
            ],
        );

        AdminRole::query()->updateOrCreate(
            ['slug' => 'event-manager'],
            [
                'name' => 'Event Manager',
                'audience' => AdminRole::AUDIENCE_BOTH,
                'description' => 'Manage events, categories, speakers, and organizers',
                'permissions' => [
                    AdminModules::DASHBOARD,
                    AdminModules::EVENTS,
                    AdminModules::EVENT_CATEGORIES,
                    AdminModules::SPEAKERS,
                    AdminModules::EVENT_ASSISTANT,
                    AdminModules::ORGANIZERS,
                ],
                'is_super' => false,
            ],
        );

        AdminRole::query()->updateOrCreate(
            ['slug' => 'organizer-events'],
            [
                'name' => 'Organizer — Events',
                'audience' => AdminRole::AUDIENCE_ORGANIZER,
                'description' => 'Organizer panel: events and bookings only',
                'permissions' => [
                    AdminModules::DASHBOARD,
                    AdminModules::EVENTS,
                    AdminModules::EVENT_CATEGORIES,
                    AdminModules::SPEAKERS,
                ],
                'is_super' => false,
            ],
        );

        AdminRole::query()->updateOrCreate(
            ['slug' => 'content-editor'],
            [
                'name' => 'Content Editor',
                'audience' => AdminRole::AUDIENCE_BOTH,
                'description' => 'Manage pages and blog content',
                'permissions' => [
                    AdminModules::DASHBOARD,
                    AdminModules::PAGES,
                    AdminModules::BLOG,
                ],
                'is_super' => false,
            ],
        );

        AdminRole::query()->updateOrCreate(
            ['slug' => 'reports-analyst'],
            [
                'name' => 'Reports Analyst',
                'audience' => AdminRole::AUDIENCE_STAFF,
                'description' => 'View classic and AI-powered reports',
                'permissions' => [
                    AdminModules::DASHBOARD,
                    AdminModules::REPORTS,
                    AdminModules::REPORT_AI,
                ],
                'is_super' => false,
            ],
        );
    }
}
