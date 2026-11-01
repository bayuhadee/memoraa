@extends('layouts.panel')
@include('owner._nav')
@section('title', 'Dashboard Owner')

@section('panel')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Dashboard</p>
            <h1 class="mt-2 text-4xl text-wood-dark">Selamat datang, {{ auth('owner')->user()->name }}</h1>
        </div>
        <a href="{{ route('owner.events.create') }}" class="btn-primary">+ Tambah Event</a>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3">
        @foreach ([['Total event', $totalEvents], ['Event mendatang', $upcomingCount], ['Total foto', $totalPhotos]] as [$label, $value])
            <div class="card p-5">
                <p class="text-xs uppercase tracking-widest text-muted">{{ $label }}</p>
                <p class="mt-1 font-serif text-4xl text-wood-dark">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 card p-5">
        <h2 class="text-xl text-wood-dark">Status pembayaran</h2>
        <div class="mt-3 flex flex-wrap gap-6">
            @foreach ($statusCounts as $label => $count)
                <div><span class="font-serif text-3xl text-wood-dark">{{ $count }}</span> <span class="text-sm text-muted">{{ $label }}</span></div>
            @endforeach
        </div>
    </div>

    <section class="mt-8">
        <h2 class="text-2xl text-wood-dark">Event mendatang</h2>
        <div class="mt-4 card overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-wood/10 text-xs uppercase tracking-wider text-muted">
                    <tr><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Event</th><th class="px-4 py-3">Tamu</th><th class="px-4 py-3">Foto</th><th class="px-4 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-wood/10">
                    @forelse ($upcoming as $event)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $event->event_date->translatedFormat('j M Y') }}</td>
                            <td class="px-4 py-3"><a href="{{ route('owner.events.show', $event) }}" class="font-medium text-wood-dark hover:underline">{{ $event->bride_groom_names }}</a><span class="block font-mono text-xs text-muted">{{ $event->event_code }}</span></td>
                            <td class="px-4 py-3">{{ $event->guests_count }}</td>
                            <td class="px-4 py-3">{{ $event->photos_count }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $event->payment_status->badgeClass() }}">{{ $event->payment_status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-muted">Belum ada event mendatang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
