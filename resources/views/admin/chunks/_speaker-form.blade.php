@php
    $isEdit = isset($speaker) && $speaker instanceof \App\Models\Speaker;
    $s = $isEdit ? $speaker : null;
@endphp
<form class="space-y-8" method="post" action="{{ $isEdit ? route('admin.speakers.update', $s) : route('admin.speakers.store') }}" enctype="multipart/form-data">
@csrf
@if($isEdit)
@method('PUT')
@endif
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-sm max-w-3xl">
<h3 class="text-headline-md font-semibold mb-6">Speaker details</h3>
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
<div class="md:col-span-1">
<label class="block text-label-md font-bold text-on-surface mb-3">Photo</label>
<div class="w-40 h-40 rounded-xl overflow-hidden bg-surface-container border border-outline-variant flex items-center justify-center mx-auto md:mx-0">
@if($isEdit && $s->photo_path)
<img src="{{ $s->photoUrl() }}" alt="" class="w-full h-full object-cover"/>
@else
<span class="material-symbols-outlined text-4xl text-outline-variant">person</span>
@endif
</div>
<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold"/>
@error('photo')<p class="text-error text-sm mt-2">{{ $message }}</p>@enderror
</div>
<div class="md:col-span-2 space-y-5">
<div>
<label class="block text-label-md font-bold text-on-surface mb-2" for="speaker_name">Name <span class="text-error">*</span></label>
<input id="speaker_name" name="name" type="text" required class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 outline-none" value="{{ old('name', $s?->name) }}"/>
@error('name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-bold text-on-surface mb-2" for="speaker_headline">Headline / role</label>
<input id="speaker_headline" name="headline" type="text" class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 outline-none" placeholder="e.g. Keynote speaker" value="{{ old('headline', $s?->headline) }}"/>
@error('headline')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-bold text-on-surface mb-2" for="speaker_sort">Directory sort order</label>
<input id="speaker_sort" name="sort_order" type="number" min="0" class="w-full max-w-xs px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 outline-none" value="{{ old('sort_order', $s?->sort_order ?? 0) }}"/>
@error('sort_order')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-bold text-on-surface mb-2" for="speaker_bio">Bio</label>
<textarea id="speaker_bio" name="bio" rows="5" class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 outline-none resize-y" placeholder="Short biography for event pages">{{ old('bio', $s?->bio) }}</textarea>
@error('bio')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
</div>
</div>
</div>
<div class="flex gap-4 pt-4">
<button type="submit" class="px-8 py-3 rounded-xl bg-primary text-white font-bold hover:opacity-90 shadow-lg shadow-primary/20">{{ $isEdit ? 'Update speaker' : 'Create speaker' }}</button>
<a href="{{ route('admin.speakers.index') }}" class="px-8 py-3 rounded-xl border border-outline-variant font-semibold text-on-surface hover:bg-surface-container-low">Cancel</a>
</div>
</form>
