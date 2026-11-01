@extends('layouts.panel')
@include('host._nav')
@section('title', 'Download ZIP')

@section('panel')
    <div class="mb-6">
        <p class="eyebrow">Download</p>
        <h1 class="mt-2 text-4xl text-wood-dark">Download Semua Foto</h1>
        <p class="mt-1 text-sm text-muted">Semua foto ({{ $photoCount }}) dikemas dalam satu file ZIP. Nama file: <code>nama-tamu_1.jpg</code>.</p>
    </div>

    <div class="card max-w-2xl p-6">
        <form method="POST" action="{{ route('host.zip.store') }}">
            @csrf
            <button type="submit" class="btn-gold" @disabled($photoCount === 0)>Buat & download ZIP</button>
        </form>
        <p class="mt-3 text-xs text-muted">
            Hingga {{ $syncMax }} foto, ZIP langsung diunduh. Jika lebih, ZIP disiapkan di latar belakang lalu link download muncul di bawah.
        </p>

        @if ($exports->isNotEmpty())
            <h2 class="mt-8 text-xl text-wood-dark">Riwayat ZIP (tersimpan {{ config('galeri.zip.keep_hours') }} jam)</h2>
            <ul class="mt-3 divide-y divide-wood/10">
                @foreach ($exports as $export)
                    <li class="flex items-center justify-between gap-4 py-3 text-sm">
                        <span>{{ $export->created_at->timezone(config('galeri.timezone'))->translatedFormat('j M Y, H:i') }}
                            @if ($export->status === 'ready') · {{ $export->file_count }} foto @endif
                        </span>
                        @if ($export->status === 'ready')
                            <a href="{{ route('host.zip.download', $export) }}" class="btn-outline btn-sm">Download</a>
                        @elseif ($export->status === 'pending')
                            <span class="text-muted" data-zip-poll="{{ route('host.zip.status', $export) }}">Sedang disiapkan…</span>
                        @else
                            <span class="text-red-700">Gagal, silakan coba lagi</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
