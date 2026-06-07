@php

    $activeNav = $activeNav ?? '';

    $isDashboard = $activeNav === 'dashboard';

    $isReports = $activeNav === 'reports';

    $isReportAi = $activeNav === 'report_ai';

    $isReportsSection = in_array($activeNav, ['reports', 'report_ai'], true);

    $isEventsSection = in_array($activeNav, ['events', 'event_categories', 'speakers', 'event_assistant', 'organizers'], true);

    $isEvents = $activeNav === 'events';

    $isEventCategories = $activeNav === 'event_categories';

    $isSpeakers = $activeNav === 'speakers';

    $isEventAssistant = $activeNav === 'event_assistant';

    $isPages = $activeNav === 'pages';

    $isBlog = $activeNav === 'blog';

    $isOrganizers = $activeNav === 'organizers';

    $isSettingsSectionNav = $activeNav === 'settings';

    $settingsSectionKey = $settingsSection ?? 'site';

    $isSettingsSite = $activeNav === 'settings' && $settingsSectionKey === 'site';

    $isSettingsHome = $activeNav === 'settings' && $settingsSectionKey === 'home';

    $isSettingsAdmin = $activeNav === 'settings' && $settingsSectionKey === 'admin';

    $isSettingsEmail = $activeNav === 'settings' && $settingsSectionKey === 'email';

    $isSettingsPayments = $activeNav === 'settings' && $settingsSectionKey === 'payments';

    $isSettingsAiReports = $activeNav === 'settings' && $settingsSectionKey === 'ai_reports';

    $isStaff = $activeNav === 'staff';

    $adminUser = auth()->user();

    $canAdmin = static function (string $module) use ($adminUser): bool {
        return $adminUser?->canAccessAdminModule($module) ?? false;
    };

    $isOrganizerAccount = $adminUser?->is_organizer ?? false;

@endphp

<aside class="admin-sidebar fixed left-0 top-0 h-full w-sidebar-width flex flex-col z-50">

    <div class="admin-sidebar__brand flex items-center gap-3">
        @if(!empty($siteLogoUrl))
            <img src="{{ $siteLogoUrl }}" alt="" class="admin-sidebar__logo w-10 h-10 rounded-lg object-contain bg-white/10"/>
        @else
            <div class="admin-sidebar__logo w-10 h-10 rounded-lg bg-primary-container flex items-center justify-center">
                <span class="material-symbols-outlined text-white">corporate_fare</span>
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <h1 class="font-semibold text-[17px] text-surface-container-lowest leading-tight truncate tracking-tight">{{ $siteDisplayName ?? 'Event Manager' }}</h1>
            <span class="admin-sidebar__role">Enterprise Admin</span>
        </div>
    </div>

    <div class="admin-sidebar-scroll flex-1 min-h-0 overflow-y-auto overscroll-y-contain">
        <nav class="admin-sidebar-nav">
            @if($canAdmin('dashboard'))
                <a class="admin-nav-link {{ $isDashboard ? 'admin-nav-link--active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="material-symbols-outlined shrink-0">dashboard</span>
                    <span>Dashboard</span>
                </a>
            @endif

            @if($canAdmin('events') || $canAdmin('event_categories') || $canAdmin('speakers') || $canAdmin('event_assistant') || $canAdmin('organizers'))
                <details class="admin-nav-group {{ $isEventsSection ? 'admin-nav-group--open' : '' }}" @if($isEventsSection) open @endif>
                    <summary class="admin-nav-group__summary">
                        <span class="material-symbols-outlined shrink-0">calendar_today</span>
                        <span class="flex-1 text-left">Events</span>
                        <span class="material-symbols-outlined admin-nav-group__chevron shrink-0">expand_more</span>
                    </summary>
                    <div class="admin-nav-sub">
                        @if($canAdmin('events'))
                            <a class="admin-nav-sub-link {{ $isEvents ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.events.index') }}">List events</a>
                        @endif
                        @if($canAdmin('event_categories'))
                            <a class="admin-nav-sub-link {{ $isEventCategories ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.event-categories.index') }}">Event categories</a>
                        @endif
                        @if($canAdmin('speakers'))
                            <a class="admin-nav-sub-link {{ $isSpeakers ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.speakers.index') }}">Speakers</a>
                        @endif
                        @if($canAdmin('event_assistant'))
                            <a class="admin-nav-sub-link {{ $isEventAssistant ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.event-assistant.index') }}">AI assistance</a>
                        @endif
                        @if($canAdmin('organizers') && ! $isOrganizerAccount)
                            <a class="admin-nav-sub-link {{ $isOrganizers ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.organizers.index') }}">Organizers</a>
                        @endif
                    </div>
                </details>
            @endif

            @if($canAdmin('reports') || $canAdmin('report_ai'))
                <details class="admin-nav-group {{ $isReportsSection ? 'admin-nav-group--open' : '' }}" @if($isReportsSection) open @endif>
                    <summary class="admin-nav-group__summary">
                        <span class="material-symbols-outlined shrink-0">analytics</span>
                        <span class="flex-1 text-left">Reports</span>
                        <span class="material-symbols-outlined admin-nav-group__chevron shrink-0">expand_more</span>
                    </summary>
                    <div class="admin-nav-sub">
                        @if($canAdmin('reports'))
                            <a class="admin-nav-sub-link {{ $isReports ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.reports.index') }}">Classic report</a>
                        @endif
                        @if($canAdmin('report_ai'))
                            <a class="admin-nav-sub-link {{ $isReportAi ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.report-ai.index') }}">AI report</a>
                        @endif
                    </div>
                </details>
            @endif

            @if($canAdmin('pages'))
                <a class="admin-nav-link {{ $isPages ? 'admin-nav-link--active' : '' }}" href="{{ route('admin.pages.index') }}">
                    <span class="material-symbols-outlined shrink-0">description</span>
                    <span>Pages</span>
                </a>
            @endif

            @if($canAdmin('blog'))
                <a class="admin-nav-link {{ $isBlog ? 'admin-nav-link--active' : '' }}" href="{{ route('admin.blog.index') }}">
                    <span class="material-symbols-outlined shrink-0">newspaper</span>
                    <span>Blog</span>
                </a>
            @endif

            @if($canAdmin('staff'))
                <a class="admin-nav-link {{ $isStaff ? 'admin-nav-link--active' : '' }}" href="{{ route('admin.staff.index') }}">
                    <span class="material-symbols-outlined shrink-0">manage_accounts</span>
                    <span>Team</span>
                </a>
            @endif

            @if($canAdmin('settings'))
                <details class="admin-nav-group {{ $isSettingsSectionNav ? 'admin-nav-group--open' : '' }}" @if($isSettingsSectionNav) open @endif>
                    <summary class="admin-nav-group__summary">
                        <span class="material-symbols-outlined shrink-0">settings</span>
                        <span class="flex-1 text-left">Settings</span>
                        <span class="material-symbols-outlined admin-nav-group__chevron shrink-0">expand_more</span>
                    </summary>
                    <div class="admin-nav-sub">
                        <a class="admin-nav-sub-link {{ $isSettingsSite ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.settings.index', ['section' => 'site']) }}">Site settings</a>
                        <a class="admin-nav-sub-link {{ $isSettingsHome ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.settings.index', ['section' => 'home']) }}">Home settings</a>
                        <a class="admin-nav-sub-link {{ $isSettingsAdmin ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.settings.index', ['section' => 'admin']) }}">Admin settings</a>
                        <a class="admin-nav-sub-link {{ $isSettingsEmail ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.settings.index', ['section' => 'email']) }}">Email settings</a>
                        <a class="admin-nav-sub-link {{ $isSettingsPayments ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.settings.index', ['section' => 'payments']) }}">Payment settings</a>
                        <a class="admin-nav-sub-link {{ $isSettingsAiReports ? 'admin-nav-sub-link--active' : '' }}" href="{{ route('admin.settings.index', ['section' => 'ai_reports']) }}">AI reports</a>
                    </div>
                </details>
            @endif
        </nav>
    </div>

    @if($canAdmin('events'))
        <div class="admin-sidebar__footer">
            <a href="{{ route('admin.events.create') }}" class="admin-sidebar-cta">
                <span class="material-symbols-outlined text-[20px]">add</span>
                Create Event
            </a>
        </div>
    @endif

</aside>
