@extends('layouts.panel')
@include('owner._nav')
@section('title', 'Daftar Event')

@section('panel')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Manajemen</p>
            <h1 class="mt-2 text-4xl text-wood-dark">Event</h1>
        </div>
        <a href="{{ route('owner.events.create') }}" class="btn-primary">+ Tambah Event</a>
    </div>

    <form method="GET" class="mt-6 flex max-w-md gap-2">
        <input type="search" name="q" value="{{ $search }}" placeholder="Cari ID event / nama mempelai" class="field py-2.5">
        <button class="btn-outline shrink-0">Cari</button>
    </form>

    <div class="mt-6 card overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-sm">
            <thead class="border-b border-wood/10 text-xs uppercase tracking-wider text-muted">
                <tr>
                    <th class="px-4 py-3">ID Event</th>
                    <th class="px-4 py-3">Mempelai</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Password</th>
                    <th class="px-4 py-3">Tamu / Foto</th>
                    <th class="px-4 py-3">Pembayaran</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-wood/10">
                @forelse ($events as $event)
                    <tr class="{{ $event->is_active ? '' : 'opacity-60' }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $event->event_code }}@unless ($event->is_active)<span class="ml-1 badge bg-stone-100 text-stone-600 ring-stone-200">nonaktif</span>@endunless</td>
                        <td class="px-4 py-3"><a href="{{ route('owner.events.show', $event) }}" class="font-medium text-wood-dark hover:underline">{{ $event->bride_groom_names }}</a></td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $event->event_date->translatedFormat('j M Y') }}</td>
                        <td class="px-4 py-3 font-mono tracking-widest">{{ $event->password_plain }}</td>
                        <td class="px-4 py-3">{{ $event->guests_count }}{{ $event->hasGuestLimit() ? ' (maks '.$event->max_guests.')' : '' }} / {{ $event->photos_count }}</td>
                        <td class="px-4 py-3">@include('owner._payment-select', ['event' => $event])</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('owner.events.show', $event) }}" class="btn-outline btn-sm">Detail</a>
                            <a href="{{ route('owner.events.edit', $event) }}" class="btn btn-sm text-wood-dark hover:bg-sand">Ubah</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-muted">Belum ada event.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $events->links() }}</div>
@endsection
