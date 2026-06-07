<div class="gallery-tile relative rounded-lg border border-outline-variant overflow-hidden bg-surface-container" data-gallery-id="{{ $img->id }}">
<img src="{{ asset('uploads/'.$img->path) }}" alt="" class="w-full h-28 object-cover"/>
<div class="flex items-center justify-end px-2 py-1.5 bg-surface-container-low">
<button type="button" class="gallery-remove-btn text-[11px] font-semibold text-error hover:underline disabled:opacity-50">Remove</button>
</div>
</div>
