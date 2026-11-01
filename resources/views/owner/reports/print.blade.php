@extends('layouts.site')
@section('title', 'Laporan Event')
@section('noindex', true)
@section('body-class', 'bg-white')

@section('content')
    <main class="mx-auto max-w-5xl p-6 text-ink">
        <div class="no-print mb-6 flex gap-2">
            <button type="button" class="btn-primary btn-sm" data-print>Cetak / Simpan PDF</button>
            <a href="{{ route('owner.reports.index', array_filter($filters)) }}" class="btn-outline btn-sm">Kembali</a>
        </div>

        <div class="flex items-center justify-between gap-6 border-b border-wood/15 pb-4">
            <h1 class="text-3xl">Laporan Event</h1>
            @include('partials.logo', ['class' => 'h-12 w-auto'])
        </div>
        <p class="mt-1 text-sm text-muted">
            Periode acara: {{ $filters['from'] ? \Illuminate\Support\Carbon::parse($filters['from'])->translatedFormat('j F Y') : 'awal' }}
            – {{ $filters['to'] ? \Illuminate\Support\Carbon::parse($filters['to'])->translatedFormat('j F Y') : 'sekarang' }}
            · Status: {{ $filters['status'] ? \App\Enums\PaymentStatus::from($filters['status'])->label() : 'Semua' }}
            · Dicetak {{ now()->translatedFormat('j F Y H:i') }}
        </p>

        <div class="mt-6 overflow-x-auto">@include('owner.reports._table')</div>
    </main>
@endsection
