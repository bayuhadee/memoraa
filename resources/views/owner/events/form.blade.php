@extends('layouts.panel')
@include('owner._nav')
@section('title', $event->exists ? 'Ubah Event' : 'Tambah Event')

@section('panel')
    <a href="{{ $event->exists ? route('owner.events.show', $event) : route('owner.events.index') }}" class="text-sm text-muted hover:text-wood-dark">&larr; Kembali</a>
    <h1 class="mt-2 text-4xl text-wood-dark">{{ $event->exists ? 'Ubah Event' : 'Tambah Event' }}</h1>

    <form method="POST" action="{{ $event->exists ? route('owner.events.update', $event) : route('owner.events.store') }}" enctype="multipart/form-data" class="mt-6 card max-w-2xl space-y-5 p-6">
        @csrf
        @if ($event->exists) @method('PUT') @endif

        <div>
            <label for="event_code" class="field-label">ID Event {{ $event->exists ? '' : '(opsional)' }}</label>
            <input id="event_code" name="event_code" type="text" maxlength="40" value="{{ old('event_code', $event->event_code) }}"
                   class="field font-mono uppercase" placeholder="mis. NIKAH-AB12 — kosongkan untuk dibuat otomatis" @if ($event->exists) required @endif>
            <p class="mt-1 text-xs text-muted">Hanya huruf, angka, dan strip. Dipakai di link: {{ url('/e') }}/<strong>ID-EVENT</strong>
                @if ($event->exists) — <span class="text-amber-700">mengubah ID akan mengubah link & QR code lama.</span>@endif
            </p>
            @error('event_code')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="bride_groom_names" class="field-label">Nama mempelai</label>
            <input id="bride_groom_names" name="bride_groom_names" type="text" maxlength="150" required
                   value="{{ old('bride_groom_names', $event->bride_groom_names) }}" class="field" placeholder="mis. Andi & Bunga">
            @error('bride_groom_names')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="event_date" class="field-label">Tanggal acara</label>
                <input id="event_date" name="event_date" type="date" required
                       value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}" class="field">
                @error('event_date')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="payment_status" class="field-label">Status pembayaran</label>
                <select id="payment_status" name="payment_status" class="field">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_status', $event->payment_status?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label for="address" class="field-label">Alamat acara</label>
            <textarea id="address" name="address" rows="3" maxlength="1000" required class="field">{{ old('address', $event->address) }}</textarea>
            @error('address')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="max_guests" class="field-label">Maksimal tamu (opsional)</label>
            <input id="max_guests" name="max_guests" type="number" min="1" max="100000" step="1" inputmode="numeric"
                   value="{{ old('max_guests', $event->max_guests) }}" class="field sm:max-w-[12rem]" placeholder="Tanpa batas">
            <p class="mt-1 text-xs text-muted">
                Jumlah perangkat/tamu yang boleh mengisi buku tamu. Kosongkan jika tanpa batas.
                @if ($event->exists) Saat ini sudah <strong>{{ $event->guests_count }}</strong> tamu mengisi.@endif
            </p>
            @error('max_guests')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="cover" class="field-label">Foto cover (opsional)</label>
            @if ($event->hasCover())
                <img src="{{ $event->coverUrl() }}" alt="Cover saat ini" class="mb-3 aspect-[16/7] w-full rounded-xl object-cover ring-1 ring-wood/10">
            @endif
            <input id="cover" name="cover" type="file" accept="{{ \App\Support\PhotoRules::accept() }}" class="field py-2.5">
            <p class="mt-1 text-xs text-muted">Tampil sebagai latar header di halaman tamu. {{ $event->hasCover() ? 'Pilih file baru untuk mengganti cover.' : '' }}</p>
            @error('cover')<p class="field-error">{{ $message }}</p>@enderror
            @if ($event->hasCover())
                <label class="mt-2 flex items-center gap-2 text-sm text-red-700">
                    <input type="hidden" name="remove_cover" value="0">
                    <input type="checkbox" name="remove_cover" value="1" class="accent-red-700" @checked(old('remove_cover'))>
                    Hapus foto cover
                </label>
            @endif
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="accent-wood-dark" @checked(old('is_active', $event->is_active))>
            Event aktif (link tamu & login host bisa dipakai)
        </label>

        @unless ($event->exists)
            <p class="rounded-xl bg-sand/60 px-4 py-3 text-sm text-muted">Password event 4 karakter akan dibuat otomatis setelah disimpan.</p>
        @endunless

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">{{ $event->exists ? 'Simpan Perubahan' : 'Buat Event' }}</button>
        </div>
    </form>
@endsection
