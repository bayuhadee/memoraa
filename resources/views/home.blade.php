@extends('layouts.site')

@section('title', config('app.name').' — Buku Tamu Digital untuk Acara')

{{--
    Gambar memakai placeholder picsum.photos.
    Ganti dengan foto sendiri: simpan di public/images lalu ubah fungsi $img di bawah.
--}}
@php
    $img = fn (string $seed, int $w, int $h) => "https://picsum.photos/seed/{$seed}/{$w}/{$h}";

    $nav = ['#tentang' => 'Tentang', '#layanan' => 'Layanan', '#cara-kerja' => 'Cara Kerja', '#galeri' => 'Galeri', '#faq' => 'FAQ'];

    $services = [
        ['01', 'Galeri Foto Bersama', 'Setiap tamu meng-upload hingga 2 foto terbaik. Foto otomatis dirapikan, dikumpulkan dalam satu galeri elegan, dan bisa dilihat semua tamu.', 'wedding-gallery'],
        ['02', 'Buku Tamu Digital', 'Nama, alamat, dan doa dari tamu tersimpan rapi—tidak ada lagi buku tamu kertas yang hilang atau tulisan yang sulit dibaca.', 'guestbook-pen'],
        ['03', 'QR Code & Download ZIP', 'Cetak QR code untuk dipajang di lokasi. Setelah acara, unduh semua foto sekaligus dalam satu file ZIP.', 'qr-table'],
    ];

    $advantages = [
        ['Tanpa aplikasi', 'Tamu cukup scan QR atau klik link dari HP—langsung bisa dipakai.', 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
        ['Tersusun otomatis', 'Foto diputar, dipotong, dan diperkecil otomatis agar galeri selalu rapi.', 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
        ['Download semua (ZIP)', 'Host mengunduh seluruh foto dalam satu klik, nama file rapi per tamu.', 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3'],
        ['Aman & privat', 'Setiap event punya link sendiri. Metadata lokasi foto dihapus otomatis.', 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z'],
    ];

    $steps = [
        ['Daftar via WhatsApp', 'Hubungi admin kami melalui WhatsApp dan sampaikan detail acara Anda.', 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z'],
        ['Event dibuat', 'Admin membuat event dan mengirimkan link galeri, QR code, serta akses login host.', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['Bagikan ke tamu', 'Bagikan link di undangan digital atau pasang QR code di meja penerima tamu.', 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z'],
        ['Tamu upload foto', 'Tamu mengisi buku tamu dan meng-upload hingga 2 foto terbaik dari HP mereka.', 'M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316ZM16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z'],
        ['Host unduh semua', 'Host melihat, mengelola, dan mengunduh seluruh foto beserta ucapan tamu.', 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3'],
    ];

    $testimonials = [
        ['Rina & Dimas', 'Pernikahan, Denpasar', 'Kami dapat ratusan foto candid dari sudut yang tidak tertangkap fotografer. Tamu senang, kami lebih senang lagi!'],
        ['Ayu Pratiwi', 'Ulang tahun ke-17, Makassar', 'Tinggal scan QR, teman-teman langsung upload. Tidak ada yang perlu install aplikasi apa pun.'],
        ['PT Nusantara Jaya', 'Family gathering', 'Download ZIP-nya sangat membantu untuk dokumentasi kantor. Prosesnya cepat dan rapi.'],
    ];

    $faqs = [
        ['Apakah tamu harus install aplikasi atau membuat akun?', 'Tidak. Tamu cukup membuka link atau scan QR code dari browser HP, lalu mengisi buku tamu dan meng-upload foto.'],
        ['Berapa foto yang bisa di-upload setiap tamu?', 'Setiap tamu dapat meng-upload hingga 2 foto terbaik, dan bisa mengganti atau menghapusnya selama periode upload.'],
        ['Sampai kapan tamu bisa meng-upload?', 'Upload dibuka sampai pukul 23:59 pada tanggal acara. Setelah itu galeri tetap bisa dilihat dan foto tetap bisa diunduh.'],
        ['Apakah galeri bisa dilihat orang lain?', 'Galeri hanya dapat diakses oleh orang yang memiliki link event Anda. Pengelolaan foto hanya bisa dilakukan host dengan password event.'],
        ['Bagaimana cara mendaftar?', 'Klik tombol "Daftar Sekarang" untuk menghubungi admin kami melalui WhatsApp. Event Anda bisa siap dalam hitungan menit.'],
    ];
@endphp

@section('content')
    {{-- ===== Navbar ===== --}}
    <header x-data="toggle" data-navbar
            data-top-class="text-white"
            data-scrolled-class="bg-cream/95 text-ink shadow-[0_1px_0_rgba(74,53,39,0.08)] backdrop-blur"
            class="group fixed inset-x-0 top-0 z-40 transition-colors duration-500">
        <div class="container-site flex h-20 items-center justify-between">
            <a href="#beranda" aria-label="{{ config('app.name') }} — ke atas">
                @include('partials.logo', ['variant' => 'light', 'class' => 'h-11 w-auto sm:h-12 group-[.is-scrolled]:hidden'])
                @include('partials.logo', ['variant' => 'color', 'class' => 'hidden h-11 w-auto sm:h-12 group-[.is-scrolled]:block'])
            </a>

            <nav class="hidden items-center gap-8 text-sm lg:flex" aria-label="Menu utama">
                @foreach ($nav as $href => $label)
                    <a href="{{ $href }}" class="opacity-85 transition hover:text-gold hover:opacity-100">{{ $label }}</a>
                @endforeach
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn-gold btn-sm">Daftar Sekarang</a>
            </nav>

            <button type="button" class="rounded-lg p-2 lg:hidden" @click="toggle" aria-label="Buka menu">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
            </button>
        </div>

        <nav x-show="open" x-cloak @click="close" class="border-t border-wood/10 bg-cream px-5 pb-6 text-ink lg:hidden" aria-label="Menu mobile">
            @foreach ($nav as $href => $label)
                <a href="{{ $href }}" class="block border-b border-wood/10 py-3.5">{{ $label }}</a>
            @endforeach
            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn-gold mt-5 w-full">Daftar Sekarang</a>
        </nav>
    </header>

    <main>
        {{-- ===== Hero slider ===== --}}
        <section id="beranda" data-hero-slider class="relative flex min-h-[100svh] items-center overflow-hidden bg-espresso text-white">
            @foreach (['hero-wedding-1', 'hero-wedding-2', 'hero-wedding-3'] as $i => $seed)
                <div class="hero-slide {{ $i === 0 ? 'is-active' : '' }}" style="background-image: url('{{ $img($seed, 1920, 1200) }}')" role="img" aria-label="Suasana acara"></div>
            @endforeach
            <div class="absolute inset-0 bg-gradient-to-b from-black/55 via-black/35 to-black/65"></div>

            <div class="relative container-site pt-24 pb-28 text-center">
                <p class="eyebrow animate-fade-in justify-center text-white/85 before:bg-white/50">Buku Tamu Digital</p>
                <h1 class="mx-auto mt-6 max-w-4xl animate-fade-in font-serif text-5xl leading-[1.05] font-medium sm:text-7xl">
                    Abadikan kehadiran dengan <em class="text-gold italic">Elegance</em>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl animate-fade-in text-base leading-relaxed text-white/85 sm:text-lg">
                    Tinggal scan QR-tanpa instal aplikasi, isi buku tamu dan berbagi moment terbaik di hari yang indah
                </p>
                <div class="mt-10 flex animate-fade-in flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn-gold w-full px-8 py-4 sm:w-auto">
                        @include('partials.icon-wa', ['class' => 'h-5 w-5']) Daftar via WhatsApp
                    </a>
                    <a href="#cara-kerja" class="btn-ghost-light w-full px-8 py-4 sm:w-auto">Lihat cara kerja</a>
                </div>
            </div>

            <div class="absolute inset-x-0 bottom-8 flex justify-center gap-2">
                @foreach (range(0, 2) as $i)
                    <button type="button" data-hero-dot class="h-1.5 w-4 rounded-full bg-white/40 transition-all duration-500" aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        </section>

        {{-- ===== Perkenalan + statistik ===== --}}
        <section id="tentang" class="py-24 sm:py-32">
            <div class="container-site grid items-center gap-14 lg:grid-cols-2">
                <div class="reveal relative">
                    <img src="{{ $img('intro-couple', 900, 1100) }}" alt="Pasangan pengantin" loading="lazy" class="aspect-[4/5] w-4/5 rounded-3xl object-cover shadow-2xl shadow-wood/20">
                    <img src="{{ $img('intro-guests', 600, 600) }}" alt="Tamu berfoto bersama" loading="lazy" class="absolute right-0 -bottom-10 aspect-square w-1/2 rounded-3xl border-8 border-cream object-cover shadow-xl">
                </div>
                <div class="reveal">
                    <p class="eyebrow">Tentang Layanan</p>
                    <h2 class="mt-4 text-4xl leading-tight text-wood-dark sm:text-5xl">Kenangan terbaik sering datang dari sudut yang tak terduga</h2>
                    <p class="mt-6 leading-relaxed text-muted">
                        Fotografer menangkap momen utama; tamu menangkap senyum, tawa, dan cerita kecil di sekitarnya.
                        {{ config('app.name') }} mengumpulkan semua itu dalam satu galeri digital yang hangat dan rapi—lengkap dengan buku tamu berisi doa dan ucapan.
                    </p>
                    <dl class="mt-10 grid grid-cols-3 gap-4 border-t border-wood/15 pt-8">
                        @foreach ([['500+', 'Event'], ['10.000+', 'Foto'], ['100%', 'Tanpa Aplikasi']] as [$num, $label])
                            <div class="flex flex-col-reverse">
                                <dt class="mt-1 text-xs uppercase tracking-widest text-muted">{{ $label }}</dt>
                                <dd class="font-serif text-4xl text-wood-dark sm:text-5xl">{{ $num }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        {{-- ===== Layanan bernomor ===== --}}
        <section id="layanan" class="bg-sand/50 py-24 sm:py-32">
            <div class="container-site">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <p class="eyebrow justify-center">Layanan Kami</p>
                    <h2 class="mt-4 text-4xl text-wood-dark sm:text-5xl">Semua yang Anda butuhkan, dalam satu link</h2>
                </div>

                <div class="mt-16 grid gap-8 md:grid-cols-3">
                    @foreach ($services as [$num, $title, $text, $seed])
                        <article class="reveal group overflow-hidden rounded-3xl bg-cream shadow-sm ring-1 ring-wood/10 transition duration-500 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-wood/10">
                            <div class="overflow-hidden">
                                <img src="{{ $img($seed, 800, 600) }}" alt="{{ $title }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition duration-700 group-hover:scale-105">
                            </div>
                            <div class="p-7">
                                <span class="font-serif text-5xl text-gold/70">{{ $num }}</span>
                                <h3 class="mt-2 text-2xl text-wood-dark">{{ $title }}</h3>
                                <p class="mt-3 text-sm leading-relaxed text-muted">{{ $text }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($advantages as [$title, $text, $icon])
                        <div class="reveal rounded-2xl bg-cream/70 p-6 ring-1 ring-wood/10">
                            <svg class="h-8 w-8 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="{{ $icon }}" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <h3 class="mt-4 font-sans text-base font-semibold text-wood-dark">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== Cara kerja ===== --}}
        <section id="cara-kerja" class="py-24 sm:py-32">
            <div class="container-site">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <p class="eyebrow justify-center">Cara Kerja</p>
                    <h2 class="mt-4 text-4xl text-wood-dark sm:text-5xl">Lima langkah sederhana</h2>
                </div>

                <ol class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ($steps as $i => [$title, $text, $icon])
                        <li class="reveal relative rounded-3xl bg-white/70 p-6 text-center ring-1 ring-wood/10 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-wood/10">
                            <span class="absolute top-4 left-5 font-serif text-sm text-gold">0{{ $i + 1 }}</span>
                            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-sand text-wood-dark">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="{{ $icon }}" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <h3 class="mt-5 text-xl text-wood-dark">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- ===== Contoh galeri ===== --}}
        <section id="galeri" class="bg-espresso py-24 text-cream sm:py-32">
            <div class="container-site">
                <div class="reveal flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p class="eyebrow">Contoh Tampilan</p>
                        <h2 class="mt-4 text-4xl sm:text-5xl">Galeri yang terasa seperti album kenangan</h2>
                    </div>
                    <p class="max-w-sm text-sm leading-relaxed text-cream/70">Grid foto responsif, lightbox dengan zoom, caption dan nama pengunggah, serta tombol download di setiap foto.</p>
                </div>

                <div class="mt-14 masonry">
                    @foreach ([['g1', 800, 1200], ['g2', 1200, 800], ['g3', 800, 1200], ['g4', 1200, 800], ['g5', 1200, 800], ['g6', 800, 1200], ['g7', 1200, 800], ['g8', 800, 1200]] as [$seed, $w, $h])
                        <figure class="reveal group overflow-hidden rounded-2xl">
                            <img src="{{ $img('sample-'.$seed, (int) ($w / 2), (int) ($h / 2)) }}" alt="Contoh foto galeri" loading="lazy"
                                 width="{{ $w / 2 }}" height="{{ $h / 2 }}" class="h-auto w-full transition duration-700 group-hover:scale-105">
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== Cerita ===== --}}
        <section class="py-24 sm:py-32">
            <div class="container-site grid items-center gap-14 lg:grid-cols-2">
                <div class="reveal order-2 lg:order-1">
                    <p class="eyebrow">Cerita Kami</p>
                    <h2 class="mt-4 text-4xl leading-tight text-wood-dark sm:text-5xl">Berawal dari foto-foto yang tersebar di puluhan grup chat</h2>
                    <p class="mt-6 leading-relaxed text-muted">
                        Setelah acara keluarga, kami selalu kesulitan mengumpulkan foto dari para tamu—tersebar di banyak HP dan grup chat, resolusinya menurun, dan sebagian akhirnya hilang.
                    </p>
                    <p class="mt-4 leading-relaxed text-muted">
                        Dari sana lahir {{ config('app.name') }}: cara sederhana dan elegan agar setiap tamu bisa ikut menyimpan cerita, dan tuan rumah menerima semuanya di satu tempat.
                    </p>
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn-primary mt-8">Konsultasi gratis</a>
                </div>
                <div class="reveal order-1 grid grid-cols-2 gap-4 lg:order-2">
                    <img src="{{ $img('story-1', 600, 800) }}" alt="Dekorasi acara" loading="lazy" class="aspect-[3/4] w-full rounded-3xl object-cover">
                    <img src="{{ $img('story-2', 600, 800) }}" alt="Tamu tersenyum" loading="lazy" class="mt-12 aspect-[3/4] w-full rounded-3xl object-cover">
                </div>
            </div>
        </section>

        {{-- ===== Testimoni ===== --}}
        <section class="bg-sand/50 py-24 sm:py-32">
            <div class="container-site">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <p class="eyebrow justify-center">Testimoni</p>
                    <h2 class="mt-4 text-4xl text-wood-dark sm:text-5xl">Kata mereka</h2>
                </div>
                <div class="mt-14 grid gap-6 md:grid-cols-3">
                    @foreach ($testimonials as [$name, $role, $quote])
                        <figure class="reveal rounded-3xl bg-cream p-8 ring-1 ring-wood/10">
                            <svg class="h-8 w-8 text-gold/60" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.6 6C6.5 7.3 4.5 10 4.5 13.6V18H10v-5.4H7.4c0-2.2 1.2-3.8 3.2-4.6L9.6 6zm9 0c-3.1 1.3-5.1 4-5.1 7.6V18H19v-5.4h-2.6c0-2.2 1.2-3.8 3.2-4.6L18.6 6z"/></svg>
                            <blockquote class="mt-4 font-serif text-xl leading-relaxed text-wood-dark italic">“{{ $quote }}”</blockquote>
                            <figcaption class="mt-6 text-sm"><strong class="block text-ink">{{ $name }}</strong><span class="text-muted">{{ $role }}</span></figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== FAQ ===== --}}
        <section id="faq" class="py-24 sm:py-32">
            <div class="container-site grid gap-12 lg:grid-cols-[1fr_1.5fr]">
                <div class="reveal">
                    <p class="eyebrow">FAQ</p>
                    <h2 class="mt-4 text-4xl text-wood-dark sm:text-5xl">Pertanyaan yang sering diajukan</h2>
                    <p class="mt-4 text-muted">Masih ada pertanyaan? Hubungi admin kami di WhatsApp <strong class="text-wood-dark">{{ $waDisplay }}</strong>.</p>
                </div>
                <div class="reveal divide-y divide-wood/15 border-y border-wood/15">
                    @foreach ($faqs as [$q, $a])
                        <div x-data="toggle">
                            <button type="button" @click="toggle" :aria-expanded="open" class="flex w-full items-center justify-between gap-6 py-5 text-left">
                                <span class="font-serif text-xl text-wood-dark">{{ $q }}</span>
                                <svg class="h-5 w-5 shrink-0 text-gold transition-transform duration-300" :class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="pb-5 text-sm leading-relaxed text-muted">{{ $a }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== Pita CTA ===== --}}
        <section class="relative overflow-hidden bg-wood-dark py-20 text-cream">
            <div class="absolute inset-0 opacity-20" style="background-image: url('{{ $img('cta-texture', 1600, 600) }}'); background-size: cover; background-position: center;"></div>
            <div class="absolute inset-0 bg-wood-dark/80"></div>
            <div class="reveal relative container-site flex flex-col items-center justify-between gap-8 text-center lg:flex-row lg:text-left">
                <div>
                    <p class="eyebrow text-gold">Mulai Sekarang</p>
                    <h2 class="mt-3 text-4xl sm:text-5xl">Siap mengabadikan momen Anda?</h2>
                    <p class="mt-3 text-cream/75">Hubungi kami—event Anda bisa siap hari ini juga.</p>
                </div>
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn-gold shrink-0 px-8 py-4">
                    @include('partials.icon-wa', ['class' => 'h-5 w-5']) Hubungi via WhatsApp
                </a>
            </div>
        </section>
    </main>

    {{-- ===== Footer ===== --}}
    <footer class="bg-espresso pt-16 pb-24 text-cream/70 sm:pb-10">
        <div class="container-site grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                @include('partials.logo', ['variant' => 'light', 'class' => 'h-16 w-auto'])
                <p class="mt-4 max-w-sm text-sm leading-relaxed">Buku tamu digital untuk pernikahan, ulang tahun, dan gathering. Tanpa aplikasi, aman, dan privat untuk setiap event.</p>
                <div class="mt-6 flex gap-3">
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="flex h-10 w-10 items-center justify-center rounded-full ring-1 ring-cream/20 transition hover:bg-gold hover:text-white hover:ring-gold" aria-label="WhatsApp">@include('partials.icon-wa', ['class' => 'h-4 w-4'])</a>
                    <a href="#" class="flex h-10 w-10 items-center justify-center rounded-full ring-1 ring-cream/20 transition hover:bg-gold hover:text-white hover:ring-gold" aria-label="Instagram">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                    </a>
                    <a href="#" class="flex h-10 w-10 items-center justify-center rounded-full ring-1 ring-cream/20 transition hover:bg-gold hover:text-white hover:ring-gold" aria-label="TikTok">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.2v12.4a2.6 2.6 0 1 1-1.8-2.5V9.7a5.8 5.8 0 1 0 5 5.7V9.1a7.4 7.4 0 0 0 4.3 1.4V7.3a4.3 4.3 0 0 1-3.2-1.5z"/></svg>
                    </a>
                </div>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold">Navigasi</p>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($nav as $href => $label)
                        <li><a href="{{ $href }}" class="hover:text-cream">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold">Kontak</p>
                <ul class="mt-4 space-y-2 text-sm">
                    <li>WhatsApp: <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="hover:text-cream">{{ $waDisplay }}</a></li>
                    <li><a href="{{ route('host.login') }}" class="hover:text-cream">Login Host</a></li>
                </ul>
            </div>
        </div>
        <div class="container-site mt-12 border-t border-cream/10 pt-6 text-xs">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Seluruh hak cipta dilindungi.
        </div>
    </footer>

    @include('partials.wa-float', ['waUrl' => $waUrl])
@endsection
