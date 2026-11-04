{{-- Header undangan: nama mempelai, tanggal, alamat --}}
<header class="relative overflow-hidden bg-espresso text-cream">
    @if ($event->hasCover())
        {{-- Cover dari owner jadi latar; lapisan gelap tipis agar teks tetap terbaca. --}}
        <img src="{{ $event->coverUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high">
        <div class="absolute inset-0 bg-gradient-to-b from-espresso/55 via-espresso/35 to-espresso/75"></div>
    @else
        <div class="absolute inset-0 opacity-[0.07] [background-image:radial-gradient(circle_at_1px_1px,#fff_1px,transparent_0)] [background-size:22px_22px]"></div>
        <div class="absolute -top-24 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-gold/25 blur-3xl"></div>
    @endif
    <div class="relative container-site py-14 text-center sm:py-20 {{ $event->hasCover() ? '[text-shadow:0_2px_12px_rgba(0,0,0,0.45)]' : '' }} {{ $compact ?? false ? '!py-10' : '' }}">
        <img src="{{ asset('images/brand/icon-light.png') }}" alt="" width="512" height="512" class="mx-auto mb-3 h-12 w-12 opacity-90">
        <p class="eyebrow justify-center text-gold before:hidden">Galeri &amp; Buku Tamu</p>
        <h1 class="mt-4 font-serif text-4xl leading-tight font-medium sm:text-6xl">
            <a href="{{ route('guest.gallery', $event) }}">{{ $event->bride_groom_names }}</a>
        </h1>
        <div class="mx-auto mt-5 flex max-w-xs items-center gap-4 text-gold">
            <span class="h-px flex-1 bg-gold/40"></span>
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.7 4.5c2.1 0 3.6 1.2 4.3 2.4.7-1.2 2.2-2.4 4.3-2.4 3.7 0 5.8 3.9 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg>
            <span class="h-px flex-1 bg-gold/40"></span>
        </div>
        <p class="mt-5 font-serif text-xl italic text-cream/90 sm:text-2xl">{{ $event->eventDateLabel() }}</p>
        @unless ($compact ?? false)
            <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-cream/70">{{ $event->address }}</p>
        @endunless
    </div>
</header>
