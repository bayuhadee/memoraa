@extends('layouts.panel')
@include('owner._nav')
@section('title', $event->bride_groom_names)

@section('panel')
    <a href="{{ route('owner.events.index') }}" class="text-sm text-muted hover:text-wood-dark">&larr; Semua event</a>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-xs text-muted">{{ $event->event_code }}</p>
            <h1 class="text-4xl text-wood-dark">{{ $event->bride_groom_names }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $event->eventDateLabel() }} · {{ $event->address }}</p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                @include('owner._payment-select', ['event' => $event])
                <span class="badge {{ $event->is_active ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-stone-100 text-stone-600 ring-stone-200' }}">{{ $event->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                <span class="badge bg-white text-muted ring-wood/15">{{ $event->isUploadOpen() ? 'Upload dibuka' : 'Upload ditutup' }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('owner.events.edit', $event) }}" class="btn-outline btn-sm">Ubah</a>
            <form method="POST" action="{{ route('owner.events.destroy', $event) }}"
                  data-confirm="Hapus event {{ $event->event_code }} beserta SELURUH foto dan buku tamunya? Tindakan ini tidak dapat dibatalkan.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger btn-sm">Hapus event</button>
            </form>
        </div>
    </div>

    @if ($event->hasCover())
        <img src="{{ $event->coverUrl() }}" alt="Foto cover {{ $event->bride_groom_names }}" class="mt-6 aspect-[16/5] w-full rounded-2xl object-cover ring-1 ring-wood/10">
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        {{-- Akses host --}}
        <section class="card p-6 lg:col-span-2">
            <h2 class="text-2xl text-wood-dark">Akses & Link</h2>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted">Link galeri tamu</dt>
                    <dd class="mt-1 break-all font-mono"><a href="{{ $event->galleryUrl() }}" target="_blank" rel="noopener" class="text-wood underline decoration-gold/50">{{ $event->galleryUrl() }}</a></dd>
                </div>
                <div>
                    <dt class="text-muted">Login host</dt>
                    <dd class="mt-1 break-all font-mono">{{ route('host.login') }}</dd>
                </div>
                <div>
                    <dt class="text-muted">ID event</dt>
                    <dd class="mt-1 font-mono text-lg">{{ $event->event_code }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Password event</dt>
                    <dd class="mt-1 flex items-center gap-3">
                        <span class="font-mono text-2xl tracking-[0.3em] text-wood-dark">{{ $event->password_plain }}</span>
                        <form method="POST" action="{{ route('owner.events.regenerate-password', $event) }}"
                              data-confirm="Buat password baru? Host yang sedang login akan otomatis keluar.">
                            @csrf
                            <button type="submit" class="btn-outline btn-sm">Generate ulang</button>
                        </form>
                    </dd>
                </div>
            </dl>

            <textarea id="host-info" readonly rows="8" class="field mt-5 font-mono text-xs">{{ $event->hostInfoText() }}</textarea>
            <button type="button" class="btn-gold mt-3" data-copy-target="#host-info">Salin info untuk host</button>
        </section>

        <section class="card p-6 text-center">
            <h2 class="text-2xl text-wood-dark">QR Code</h2>
            <img src="{{ route('owner.events.qrcode', $event) }}" alt="QR code link galeri" class="mx-auto mt-4 w-full max-w-[240px] rounded-xl bg-white p-2 ring-1 ring-wood/10">
            <a href="{{ route('owner.events.qrcode', [$event, 'download' => 1]) }}" class="btn-primary btn-sm mt-4">Download PNG</a>
            <p class="mt-6 text-sm text-muted">
                {{ $event->guests_count }}{{ $event->hasGuestLimit() ? ' / '.$event->max_guests : '' }} tamu · {{ $event->photos_count }} foto
            </p>
            @if ($event->hasGuestLimit() && $event->guests_count >= $event->max_guests)
                <p class="mt-1 text-xs font-medium text-amber-700">Kuota buku tamu penuh</p>
            @endif
        </section>
    </div>

    {{-- Foto --}}
    <section class="mt-10">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <h2 class="text-3xl text-wood-dark">Foto</h2>
            @if ($event->photos_count > 0)
                <details class="card w-full max-w-md p-4 text-sm sm:w-auto">
                    <summary class="cursor-pointer font-medium text-red-700">Reset semua foto…</summary>
                    <form method="POST" action="{{ route('owner.events.reset-photos', $event) }}" class="mt-3 space-y-3">
                        @csrf
                        <p class="text-muted">Semua foto event ini akan dihapus permanen dari storage. Buku tamu tetap ada. Ketik <strong class="font-mono">{{ $event->event_code }}</strong> untuk konfirmasi.</p>
                        <input type="text" name="confirm_code" required autocomplete="off" class="field font-mono uppercase" placeholder="{{ $event->event_code }}">
                        @error('confirm_code')<p class="field-error">{{ $message }}</p>@enderror
                        <button type="submit" class="btn-danger btn-sm">Hapus semua foto</button>
                    </form>
                </details>
            @endif
        </div>

        @include('partials.admin-photo-grid', [
            'photos' => $photos,
            'bulkAction' => route('owner.events.photos.bulk-destroy', $event),
            'singleRoute' => fn ($photo) => route('owner.events.photos.destroy', [$event, $photo]),
        ])
    </section>

    {{-- Buku tamu --}}
    <section class="mt-10">
        <h2 class="text-3xl text-wood-dark">Buku Tamu</h2>
        <div class="mt-4 card overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-wood/10 text-xs uppercase tracking-wider text-muted">
                    <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Alamat</th><th class="px-4 py-3">Ucapan</th><th class="px-4 py-3">Foto</th><th class="px-4 py-3">Waktu</th></tr>
                </thead>
                <tbody class="divide-y divide-wood/10">
                    @forelse ($guests as $guest)
                        <tr class="align-top">
                            <td class="px-4 py-3 font-medium">{{ $guest->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $guest->address }}</td>
                            <td class="px-4 py-3 whitespace-pre-line">{{ $guest->message ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $guest->photos_count }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $guest->created_at->timezone(config('galeri.timezone'))->translatedFormat('j M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-muted">Belum ada tamu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
