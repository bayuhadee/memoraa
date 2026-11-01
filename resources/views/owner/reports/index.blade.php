@extends('layouts.panel')
@include('owner._nav')
@section('title', 'Laporan')

@section('panel')
    <p class="eyebrow">Laporan</p>
    <h1 class="mt-2 text-4xl text-wood-dark">Laporan Event</h1>

    <form method="GET" action="{{ route('owner.reports.index') }}" class="mt-6 card grid gap-4 p-5 sm:grid-cols-4 sm:items-end">
        <div>
            <label for="from" class="field-label">Tanggal acara mulai</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="field py-2.5">
        </div>
        <div>
            <label for="to" class="field-label">Sampai</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="field py-2.5">
        </div>
        <div>
            <label for="status" class="field-label">Status pembayaran</label>
            <select id="status" name="status" class="field py-2.5">
                <option value="">Semua</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary flex-1">Tampilkan</button>
            <a href="{{ route('owner.reports.index') }}" class="btn-outline">Reset</a>
        </div>
    </form>

    @php($query = array_filter($filters))
    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('owner.reports.export', $query) }}" class="btn-gold btn-sm">Export Excel (CSV)</a>
        <a href="{{ route('owner.reports.print', $query) }}" target="_blank" rel="noopener" class="btn-outline btn-sm">Cetak / PDF</a>
    </div>

    <div class="mt-4 card overflow-x-auto">
        @include('owner.reports._table')
    </div>
@endsection
