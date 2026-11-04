@extends('layouts.site')

@section('title', 'Galeri '.$event->bride_groom_names)
@section('noindex', true)

@section('content')
    @include('guest.partials.header', ['event' => $event])

    <main class="container-site -mt-8 pb-20">
        {{-- Kartu aksi utama --}}
        <div class="relative z-10 card mx-auto max-w-2xl p-6 text-center sm:p-8">
            @include('partials.flash')

            @if ($uploadOpen && $guestbookFull)
                <p class="font-serif text-2xl text-wood-dark">Mohon maaf buku tamu telah penuh</p>
                <p class="mt-2 text-sm text-muted">Terima kasih atas doa dan kehadiran Anda. Galeri tetap bisa dilihat dan foto bisa di-download.</p>
            @elseif ($uploadOpen)
                <p class="font-serif text-2xl text-wood-dark">Bagikan momen terbaik Anda</p>
                <p class="mt-2 text-sm text-muted">Isi buku tamu lalu upload hingga {{ config('galeri.photos.max_per_guest') }} foto terbaik. Tanpa aplikasi, tanpa daftar akun.</p>
                <a href="{{ $hasGuest ? route('guest.my-photos', $event) : route('guest.guestbook', $event) }}" class="btn-primary mt-5 w-full sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                    {{ $hasGuest ? 'Foto Saya & Ubah Ucapan' : 'Isi Buku Tamu & Upload Foto' }}
                </a>
                <p class="mt-3 text-xs text-muted">Upload dibuka sampai {{ $event->eventDateLabel() }}, pukul 23:59 {{ now(config('galeri.timezone'))->format('T') }}.</p>
            @else
                <p class="font-serif text-2xl text-wood-dark">Periode upload telah berakhir</p>
                <p class="mt-2 text-sm text-muted">Terima kasih atas doa dan kehadiran Anda. Galeri tetap bisa dilihat dan foto bisa di-download.</p>
                @if ($hasGuest)
                    <a href="{{ route('guest.my-photos', $event) }}" class="btn-outline mt-5">Lihat foto saya</a>
                @endif
            @endif
        </div>

        <div class="mt-6 flex justify-center gap-8 text-center text-sm text-muted">
            <div><span class="block font-serif text-3xl text-wood-dark">{{ $photos->total() }}</span>foto</div>
            <div><span class="block font-serif text-3xl text-wood-dark">{{ $guestCount }}</span>tamu</div>
            <div><span class="block font-serif text-3xl text-wood-dark">{{ $wishes->count() }}</span>ucapan</div>
        </div>

        {{-- Galeri --}}
        <section class="mt-14" aria-labelledby="judul-galeri">
            <div class="mb-6 text-center">
                <p class="eyebrow justify-center">Momen Bahagia</p>
                <h2 id="judul-galeri" class="mt-2 text-4xl text-wood-dark">Foto dari Tamu</h2>
            </div>

            @if ($photos->isEmpty())
                <div class="card px-6 py-14 text-center text-muted">Belum ada foto. Jadilah yang pertama membagikan momen!</div>
            @else
                <div class="masonry" data-gallery data-infinite data-next="{{ $photos->nextPageUrl() }}">
                    @include('guest.partials.photo-items', ['photos' => $photos])
                </div>
                <div data-infinite-sentinel class="flex justify-center py-8 text-sm text-muted">Memuat foto…</div>
                <div data-infinite-fallback class="mt-4">{{ $photos->onEachSide(1)->links() }}</div>
            @endif
        </section>

        {{-- Ucapan --}}
        <section class="mt-16" aria-labelledby="judul-ucapan">
            <div class="mb-6 text-center">
                <p class="eyebrow justify-center">Doa &amp; Harapan</p>
                <h2 id="judul-ucapan" class="mt-2 text-4xl text-wood-dark">Buku Tamu</h2>
            </div>

            @forelse ($wishes as $wish)
                @if ($loop->first)<div class="mx-auto grid max-w-3xl gap-4">@endif
                <article class="card reveal p-5">
                    <div class="flex items-baseline justify-between gap-4">
                        <h3 class="font-serif text-xl text-wood-dark">{{ $wish->name }}</h3>
                        <time class="shrink-0 text-xs text-muted" datetime="{{ $wish->created_at->toIso8601String() }}">
                            {{ $wish->created_at->timezone(config('galeri.timezone'))->translatedFormat('j M Y, H:i') }}
                        </time>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink/80">{{ $wish->message }}</p>
                </article>
                @if ($loop->last)</div>@endif
            @empty
                <div class="card mx-auto max-w-3xl px-6 py-10 text-center text-muted">Belum ada ucapan.</div>
            @endforelse
        </section>
    </main>

    <footer class="border-t border-wood/10 py-8 text-center text-xs text-muted">
        <p>Dibuat dengan ♥ oleh</p>
        <a href="{{ route('home') }}" class="mt-2 inline-block transition hover:opacity-80">
            @include('partials.logo', ['class' => 'h-10 w-auto'])
        </a>
    </footer>
@endsection
