{{-- Satu foto di grid galeri: thumbnail + link lightbox. Semua teks di-escape Blade. --}}
@php($v = $photo->updated_at?->timestamp)
<figure class="group relative overflow-hidden rounded-xl bg-sand">
    <a href="{{ route('media.show', [$photo, 'full']) }}?v={{ $v }}"
       data-pswp
       data-pswp-width="{{ $photo->width }}"
       data-pswp-height="{{ $photo->height }}"
       data-author="{{ $photo->guest?->name }}"
       data-caption="{{ $photo->caption }}"
       data-download="{{ route('media.download', $photo) }}"
       class="block">
        <img src="{{ route('media.show', [$photo, 'thumb']) }}?v={{ $v }}"
             alt="Foto dari {{ $photo->guest?->name }}"
             width="{{ $photo->thumbWidth() }}" height="{{ $photo->thumbHeight() }}"
             loading="lazy" decoding="async"
             class="h-auto w-full transition duration-700 group-hover:scale-[1.03]">
    </a>
    <figcaption class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/65 to-transparent px-3 pt-8 pb-2.5 text-white">
        <span class="block truncate font-serif text-base leading-tight">{{ $photo->guest?->name }}</span>
        @if ($photo->caption)
            <span class="line-clamp-1 text-xs text-white/80">{{ $photo->caption }}</span>
        @endif
    </figcaption>
</figure>
