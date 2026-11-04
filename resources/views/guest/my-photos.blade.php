@extends('layouts.site')

@section('title', 'Foto Saya · '.$event->bride_groom_names)
@section('noindex', true)

@section('content')
    @include('guest.partials.header', ['event' => $event, 'compact' => true])

    <main class="container-site -mt-6 pb-16">
        <div class="relative z-10 card mx-auto max-w-3xl p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('guest.gallery', $event) }}" class="text-sm text-muted hover:text-wood-dark">&larr; Kembali ke galeri</a>
                @if ($uploadOpen)
                    <a href="{{ route('guest.guestbook', $event) }}" class="text-sm text-wood underline decoration-gold/50 underline-offset-4">Ubah ucapan</a>
                @endif
            </div>

            <h2 class="mt-3 text-3xl text-wood-dark">Foto Saya</h2>
            <p class="mt-1 text-sm text-muted">Halo, <strong class="text-wood-dark">{{ $guest->name }}</strong>. Foto di bawah ini hanya bisa dikelola dari perangkat ini.</p>

            <div class="mt-6">@include('partials.flash')</div>

            {{-- Kuota --}}
            <div class="flex items-center justify-between rounded-xl bg-sand/60 px-4 py-3 text-sm">
                <span>Sisa kuota: <strong>{{ $remaining }} foto</strong></span>
                <span class="text-muted">{{ $photos->count() }} / {{ $max }} terpakai</span>
            </div>

            {{-- Foto yang sudah di-upload --}}
            @if ($photos->isNotEmpty())
                <div class="mt-6 grid gap-5 sm:grid-cols-2" data-gallery>
                    @foreach ($photos as $photo)
                        <div class="overflow-hidden rounded-2xl ring-1 ring-wood/10">
                            <div class="relative bg-sand">
                                @if ($photo->hasImage())
                                    @php($v = $photo->updated_at?->timestamp)
                                    <a href="{{ route('media.show', [$photo, 'full']) }}?v={{ $v }}" data-pswp
                                       data-pswp-width="{{ $photo->width }}" data-pswp-height="{{ $photo->height }}"
                                       data-author="{{ $guest->name }}" data-caption="{{ $photo->caption }}"
                                       data-download="{{ route('media.download', $photo) }}" class="block">
                                        <img src="{{ route('media.show', [$photo, 'thumb']) }}?v={{ $v }}" alt="Foto saya"
                                             width="{{ $photo->thumbWidth() }}" height="{{ $photo->thumbHeight() }}" class="h-auto w-full">
                                    </a>
                                @endif

                                @if ($photo->isProcessing())
                                    <div class="flex aspect-[4/3] w-full flex-col items-center justify-center gap-3 text-sm text-wood {{ $photo->hasImage() ? 'absolute inset-0 bg-cream/80 backdrop-blur-sm' : '' }}">
                                        <svg class="h-6 w-6 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                        Sedang diproses…
                                    </div>
                                @elseif ($photo->isFailed())
                                    <div class="flex aspect-[4/3] w-full flex-col items-center justify-center gap-2 px-6 text-center text-sm text-red-800">
                                        <strong>Foto gagal diproses.</strong>
                                        <span>File mungkin rusak atau formatnya tidak didukung. Silakan ganti atau hapus.</span>
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3 bg-white p-4">
                                @if ($uploadOpen)
                                    <form method="POST" action="{{ route('guest.photos.update', [$event, $photo]) }}" class="space-y-2">
                                        @csrf
                                        @method('PUT')
                                        <label class="field-label text-xs" for="caption-{{ $photo->uuid }}">Caption (opsional)</label>
                                        <textarea id="caption-{{ $photo->uuid }}" name="caption" rows="2" maxlength="{{ config('galeri.photos.caption_max') }}"
                                                  class="field text-sm" placeholder="Tulis pesan untuk foto ini…">{{ $photo->caption }}</textarea>
                                        <button type="submit" class="btn-outline btn-sm">Simpan caption</button>
                                    </form>

                                    <div class="flex flex-wrap gap-2 border-t border-wood/10 pt-3">
                                        @unless ($photo->isProcessing())
                                            <form method="POST" action="{{ route('guest.photos.replace', [$event, $photo]) }}" enctype="multipart/form-data" data-replace-form class="contents">
                                                @csrf
                                                <label class="btn-outline btn-sm cursor-pointer">
                                                    Ganti foto
                                                    <input type="file" name="photo" accept="{{ $accept }}" class="sr-only">
                                                </label>
                                                <noscript><button type="submit" class="btn-outline btn-sm">Upload pengganti</button></noscript>
                                                <div data-uploader-error hidden class="w-full rounded-lg bg-red-50 px-3 py-2 text-xs text-red-800"></div>
                                                <div data-uploader-progress hidden class="w-full">
                                                    <div class="h-2 overflow-hidden rounded-full bg-sand"><div data-bar class="h-full w-0 bg-gold transition-all"></div></div>
                                                    <p data-pct class="mt-1 text-xs text-muted"></p>
                                                </div>
                                            </form>
                                        @endunless

                                        <form method="POST" action="{{ route('guest.photos.destroy', [$event, $photo]) }}" data-confirm="Hapus foto ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm text-red-700 hover:bg-red-50">Hapus</button>
                                        </form>
                                    </div>
                                @else
                                    <p class="text-sm text-ink/80">{{ $photo->caption ?: 'Tanpa caption' }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($photos->contains(fn ($p) => $p->isProcessing()))
                    <div data-poll-status="{{ route('guest.my-photos.status', $event) }}" class="mt-4 text-center text-xs text-muted">
                        Halaman akan diperbarui otomatis setelah foto selesai diproses.
                    </div>
                @endif
            @endif

            {{-- Form upload --}}
            @if (! $uploadOpen)
                <div class="mt-6 rounded-xl border border-wood/15 px-4 py-4 text-center text-sm text-muted">
                    Periode upload telah berakhir.
                </div>
            @elseif ($remaining > 0)
                <form method="POST" action="{{ route('guest.photos.store', $event) }}" enctype="multipart/form-data"
                      class="mt-8" data-uploader data-remaining="{{ $remaining }}" data-caption-max="{{ config('galeri.photos.caption_max') }}">
                    @csrf
                    <h3 class="font-serif text-2xl text-wood-dark">Upload Foto</h3>
                    <p class="mt-1 text-sm text-muted">Pilih hingga {{ $remaining }} foto (JPG, PNG, WEBP{{ str_contains($accept, 'heic') ? ', HEIC' : '' }}, GIF). Foto otomatis dirapikan ukurannya.</p>

                    <label data-uploader-dropzone class="mt-4 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-linen bg-cream px-6 py-10 text-center transition hover:border-gold">
                        <svg class="h-10 w-10 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 16.5V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-1.5M7.5 9 12 4.5 16.5 9M12 4.5V15" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="font-medium text-wood-dark">Ketuk untuk memilih foto</span>
                        <span class="text-xs text-muted">atau seret &amp; lepas file ke sini</span>
                        <input type="file" name="photos[]" accept="{{ $accept }}" {{ $remaining > 1 ? 'multiple' : '' }} class="sr-only" data-uploader-input>
                    </label>

                    <div data-uploader-previews class="mt-5 grid gap-4 sm:grid-cols-2"></div>

                    <div data-uploader-error hidden class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert"></div>

                    <div data-uploader-progress hidden class="mt-4">
                        <div class="h-2.5 overflow-hidden rounded-full bg-sand"><div data-bar class="h-full w-0 rounded-full bg-gold transition-all"></div></div>
                        <p data-pct class="mt-1.5 text-center text-xs text-muted"></p>
                    </div>

                    <button type="submit" class="btn-primary mt-5 w-full" data-uploader-submit>
                        Upload <span data-count>0</span> foto
                    </button>
                </form>
            @else
                <div class="mt-6 rounded-xl bg-sand/60 px-4 py-4 text-center text-sm text-muted">
                    Kuota foto Anda sudah penuh. Anda masih bisa mengganti atau menghapus foto di atas.
                </div>
            @endif

            <a href="{{ route('guest.gallery', $event) }}" class="btn-outline mt-4 w-full">&larr; Kembali ke Galeri</a>
        </div>
    </main>
@endsection
