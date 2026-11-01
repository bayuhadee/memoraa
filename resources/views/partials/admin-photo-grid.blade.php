{{--
    Grid foto untuk host & owner: lightbox + pilih banyak + hapus.
    Variabel: $photos, $bulkAction (URL DELETE bulk), $singleRoute (closure foto => URL DELETE)
--}}
@if ($photos->isEmpty())
    <div class="card px-6 py-14 text-center text-muted">Belum ada foto yang di-upload tamu.</div>
@else
    <form method="POST" action="{{ $bulkAction }}" data-bulk-form>
        @csrf
        @method('DELETE')

        <div class="sticky top-0 z-20 -mx-1 mb-4 flex flex-wrap items-center gap-3 rounded-xl bg-cream/95 px-1 py-3 backdrop-blur">
            <button type="button" class="btn-outline btn-sm" data-bulk-toggle-all>Pilih / batal pilih semua</button>
            <button type="submit" class="btn-danger btn-sm" data-bulk-submit disabled
                    data-confirm="Hapus semua foto yang dipilih? Tindakan ini tidak dapat dibatalkan.">
                Hapus terpilih (<span data-bulk-count>0</span>)
            </button>
            <span class="text-sm text-muted">{{ $photos->total() }} foto</span>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" data-gallery>
            @foreach ($photos as $photo)
                @php($v = $photo->updated_at?->timestamp)
                <div class="group relative overflow-hidden rounded-xl bg-sand ring-gold transition" data-bulk-item>
                    <a href="{{ route('media.show', [$photo, 'full']) }}?v={{ $v }}" data-pswp
                       data-pswp-width="{{ $photo->width }}" data-pswp-height="{{ $photo->height }}"
                       data-author="{{ $photo->guest?->name }}" data-caption="{{ $photo->caption }}"
                       data-download="{{ route('media.download', $photo) }}" class="block aspect-square">
                        <img src="{{ route('media.show', [$photo, 'thumb']) }}?v={{ $v }}" alt="Foto dari {{ $photo->guest?->name }}"
                             loading="lazy" class="h-full w-full object-cover">
                    </a>
                    <label class="absolute top-2 left-2 flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg bg-white/90 shadow">
                        <input type="checkbox" name="ids[]" value="{{ $photo->uuid }}" class="h-4 w-4 accent-wood-dark" aria-label="Pilih foto">
                    </label>
                    <button type="submit" formaction="{{ $singleRoute($photo) }}"
                            data-confirm="Hapus foto dari {{ $photo->guest?->name }}?"
                            class="absolute top-2 right-2 rounded-lg bg-white/90 px-2.5 py-1.5 text-xs font-medium text-red-700 shadow opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                        Hapus
                    </button>
                    <div class="border-t border-wood/10 bg-white px-3 py-2">
                        <p class="truncate text-sm font-medium">{{ $photo->guest?->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $photo->caption ?: '—' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </form>

    <div class="mt-6">{{ $photos->links() }}</div>
@endif
