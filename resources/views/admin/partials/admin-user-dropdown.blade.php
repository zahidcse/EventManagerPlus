@php
    $menuUser = $user ?? auth()->user();
    $avatarSize = $avatarSize ?? 'w-9 h-9';
    $avatarTextClass = $avatarTextClass ?? 'text-sm';
    $compactUserLabels = !empty($compactUserLabels);
@endphp
<details class="admin-user-dropdown relative">
<summary class="list-none cursor-pointer flex items-center gap-3 rounded-full hover:bg-black/5 dark:hover:bg-white/10 py-1 pl-1 pr-2 sm:pr-3 transition-colors select-none [&::-webkit-details-marker]:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25">
@include('admin.partials.admin-user-avatar', ['user' => $menuUser, 'sizeClass' => $avatarSize, 'textClass' => $avatarTextClass])
<div class="@if($compactUserLabels) hidden sm:block text-left @else hidden lg:block text-right @endif min-w-0 max-w-[200px]">
@if($compactUserLabels)
<span class="font-body-md text-body-md font-semibold truncate block text-on-surface">{{ $menuUser->name }}</span>
<span class="text-xs text-on-surface-variant truncate block">{{ $menuUser->email }}</span>
@else
<p class="text-xs font-bold text-on-surface truncate">{{ $menuUser->name }}</p>
<p class="text-[10px] text-on-surface-variant truncate">{{ $menuUser->email }}</p>
@endif
</div>
<span class="material-symbols-outlined admin-user-dropdown-caret text-on-surface-variant text-[20px] shrink-0 hidden sm:inline">expand_more</span>
</summary>
<div class="absolute right-0 top-[calc(100%+10px)] min-w-[260px] rounded-xl bg-surface-container-lowest dark:bg-[#2b2930] border border-outline-variant shadow-xl py-2 z-[60]" role="menu">
<div class="px-4 py-3 border-b border-outline-variant">
<p class="text-sm font-semibold text-on-surface truncate">{{ $menuUser->name }}</p>
<p class="text-xs text-on-surface-variant truncate">{{ $menuUser->email }}</p>
</div>
<div class="px-2 py-1">
<a href="{{ route('admin.profile.edit') }}" role="menuitem" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-on-surface hover:bg-surface-container-low dark:hover:bg-white/5 transition-colors">
<span class="material-symbols-outlined text-[20px] text-on-surface-variant shrink-0">person</span>
Profile
</a>
</div>
<form method="post" action="{{ route('admin.logout') }}" class="px-2 pt-1 pb-1 border-t border-outline-variant">
@csrf
<button type="submit" class="w-full flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-on-surface hover:bg-surface-container-low dark:hover:bg-white/5 transition-colors text-left">
<span class="material-symbols-outlined text-[20px] text-on-surface-variant shrink-0">logout</span>
Sign out
</button>
</form>
</div>
</details>
