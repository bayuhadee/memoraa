{{-- Dropdown status pembayaran yang langsung tersimpan saat diubah --}}
<form method="POST" action="{{ route('owner.events.payment-status', $event) }}" class="inline">
    @csrf
    @method('PATCH')
    <label class="sr-only" for="ps-{{ $event->id }}">Status pembayaran</label>
    <select id="ps-{{ $event->id }}" name="payment_status" data-autosubmit
            class="badge cursor-pointer border-0 py-1 pr-7 {{ $event->payment_status->badgeClass() }}">
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected($event->payment_status->value === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="text-xs underline">Simpan</button></noscript>
</form>
