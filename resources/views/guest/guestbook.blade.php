@extends('layouts.site')

@section('title', ($guest ? 'Ubah Buku Tamu' : 'Isi Buku Tamu').' · '.$event->bride_groom_names)
@section('noindex', true)

@section('content')
    @include('guest.partials.header', ['event' => $event, 'compact' => true])

    <main class="container-site -mt-6 pb-16">
        <div class="relative z-10 card mx-auto max-w-xl p-6 sm:p-8">
            <a href="{{ route('guest.gallery', $event) }}" class="text-sm text-muted hover:text-wood-dark">&larr; Kembali ke galeri</a>
            <h2 class="mt-3 text-3xl text-wood-dark">{{ $guest ? 'Ubah Buku Tamu' : 'Isi Buku Tamu' }}</h2>
            <p class="mt-1 text-sm text-muted">
                @if ($guest)
                    Anda sudah mengisi buku tamu dari perangkat ini. Silakan perbarui jika ada yang ingin diubah.
                @else
                    Satu perangkat hanya dapat mengisi buku tamu satu kali, namun bisa diubah kembali nanti.
                @endif
            </p>

            <div class="mt-6">@include('partials.flash', ['hideErrorSummary' => true])</div>

            <form method="POST" action="{{ route('guest.guestbook.store', $event) }}" class="space-y-5">
                @csrf
                @if ($guest) @method('PUT') @endif

                {{-- Honeypot anti-bot --}}
                <div class="hp-field" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="name" class="field-label">Nama <span class="text-red-700">*</span></label>
                    <input id="name" name="name" type="text" maxlength="100" required autocomplete="name"
                           value="{{ old('name', $guest?->name) }}" class="field" placeholder="Nama Anda">
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="address" class="field-label">Alamat <span class="text-red-700">*</span></label>
                    <input id="address" name="address" type="text" maxlength="255" required
                           value="{{ old('address', $guest?->address) }}" class="field" placeholder="Kota / alamat singkat">
                    @error('address')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="message" class="field-label">Ucapan &amp; doa <span class="text-red-700">*</span></label>
                    <textarea id="message" name="message" rows="5" maxlength="1000" required class="field"
                              placeholder="Tulis ucapan untuk kedua mempelai…">{{ old('message', $guest?->message) }}</textarea>
                    @error('message')<p class="field-error">{{ $message }}</p>@enderror
                    @error('website')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary w-full">
                    {{ $guest ? 'Simpan Perubahan' : 'Simpan & Lanjut Upload Foto' }}
                </button>
            </form>
        </div>
    </main>
@endsection
