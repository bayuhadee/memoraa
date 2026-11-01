@php
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
        'error' => 'border-red-200 bg-red-50 text-red-900',
    ];
@endphp
@foreach ($styles as $type => $class)
    @if (session($type))
        <div role="{{ $type === 'error' ? 'alert' : 'status' }}" class="mb-5 rounded-xl border px-4 py-3 text-sm {{ $class }}">
            {{ session($type) }}
        </div>
    @endif
@endforeach
@if ($errors->any() && empty($hideErrorSummary))
    <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
