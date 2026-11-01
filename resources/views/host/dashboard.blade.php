@extends('layouts.panel')
@include('host._nav')
@section('title', 'Dashboard Host')

@section('panel')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Ringkasan</p>
            <h1 class="mt-2 text-4xl text-wood-dark">{{ $event->bride_groom_names }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $event->eventDateLabel() }} · {{ $event->address }}</p>
        </div>
        <span class="badge {{ $event->isUploadOpen() ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-stone-100 text-stone-700 ring-stone-200' }}">
            {{ $event->isUploadOpen() ? 'Upload tamu dibuka' : 'Periode upload berakhir' }}
        </span>
    </div>

    <div class="mt-8 grid grid-cols-3 gap-3 sm:gap-5">
        @foreach ([['Tamu', $guestCount], ['Foto', $photoCount], ['Ucapan', $wishCount]] as [$label, $value])
            <div class="card p-4 sm:p-6">
                <p class="text-xs uppercase tracking-widest text-muted">{{ $label }}</p>
                <p class="mt-1 font-serif text-4xl text-wood-dark sm:text-5xl">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <section class="card p-6 lg:col-span-2">
            <h2 class="text-2xl text-wood-dark">Link untuk tamu</h2>
            <p class="mt-1 text-sm text-muted">Bagikan link ini atau pasang QR code di lokasi acara.</p>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <input id="event-link" type="text" readonly value="{{ $event->galleryUrl() }}" class="field font-mono text-sm">
                <button type="button" class="btn-primary shrink-0" data-copy-target="#event-link">Salin link</button>
            </div>
            <a href="{{ $event->galleryUrl() }}" target="_blank" rel="noopener" class="mt-3 inline-block text-sm text-wood underline decoration-gold/50 underline-offset-4">Buka halaman galeri &rarr;</a>

            @if ($latestPhotos->isNotEmpty())
                <h3 class="mt-8 text-xl text-wood-dark">Foto terbaru</h3>
                <div class="mt-3 grid grid-cols-4 gap-2" data-gallery>
                    @foreach ($latestPhotos as $photo)
                        <a href="{{ route('media.show', [$photo, 'full']) }}" data-pswp data-pswp-width="{{ $photo->width }}" data-pswp-height="{{ $photo->height }}"
                           data-author="{{ $photo->guest?->name }}" data-caption="{{ $photo->caption }}" data-download="{{ route('media.download', $photo) }}"
                           class="block aspect-square overflow-hidden rounded-lg bg-sand">
                            <img src="{{ route('media.show', [$photo, 'thumb']) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('host.photos.index') }}" class="btn-outline btn-sm mt-4">Kelola semua foto</a>
            @endif
        </section>

        <section class="card p-6 text-center">
            <h2 class="text-2xl text-wood-dark">QR Code</h2>
            <img src="{{ route('host.qrcode') }}" alt="QR code link galeri" class="mx-auto mt-4 w-full max-w-[240px] rounded-xl bg-white p-2 ring-1 ring-wood/10">
            <a href="{{ route('host.qrcode', ['download' => 1]) }}" class="btn-gold btn-sm mt-4">Download PNG</a>
        </section>
    </div>
@endsection
