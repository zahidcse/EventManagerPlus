<div class="max-w-5xl mx-auto p-8">
<!-- Breadcrumbs & Header -->
<div class="mb-8">
<nav class="flex text-label-md text-on-surface-variant mb-2 gap-2">
<a class="hover:text-primary transition-colors" href="{{ route('admin.organizers.index') }}">Organizers</a>
<span class="material-symbols-outlined text-[14px]" data-icon="chevron_right">chevron_right</span>
<span class="text-primary font-semibold">Create Organizer</span>
</nav>
<h2 class="text-display-lg font-bold text-on-surface tracking-tight">Create New Organizer</h2>
<p class="text-body-lg text-on-surface-variant mt-1">Onboard a new event partner to the EventFlow ecosystem.</p>
</div>
@include('admin.chunks._organizer-form')
</div>
