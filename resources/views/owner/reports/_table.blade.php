<table class="w-full min-w-[860px] text-left text-sm">
    <thead class="border-b border-wood/15 text-xs uppercase tracking-wider text-muted">
        <tr>
            <th class="px-4 py-3">ID Event</th>
            <th class="px-4 py-3">Nama Mempelai</th>
            <th class="px-4 py-3">Tanggal Acara</th>
            <th class="px-4 py-3">Alamat</th>
            <th class="px-4 py-3 text-right">Tamu</th>
            <th class="px-4 py-3 text-right">Foto</th>
            <th class="px-4 py-3">Status</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-wood/10">
        @forelse ($events as $event)
            <tr class="align-top">
                <td class="px-4 py-3 font-mono text-xs">{{ $event->event_code }}</td>
                <td class="px-4 py-3">{{ $event->bride_groom_names }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ $event->event_date->translatedFormat('j M Y') }}</td>
                <td class="px-4 py-3 text-muted">{{ $event->address }}</td>
                <td class="px-4 py-3 text-right">{{ $event->guests_count }}</td>
                <td class="px-4 py-3 text-right">{{ $event->photos_count }}</td>
                <td class="px-4 py-3"><span class="badge {{ $event->payment_status->badgeClass() }}">{{ $event->payment_status->label() }}</span></td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-muted">Tidak ada event pada filter ini.</td></tr>
        @endforelse
    </tbody>
    <tfoot class="border-t-2 border-wood/20 font-medium">
        <tr>
            <td class="px-4 py-3" colspan="4">Total: {{ $summary['events'] }} event
                @foreach ($summary['by_status'] as $label => $count)
                    <span class="ml-3 text-xs font-normal text-muted">{{ $label }}: {{ $count }}</span>
                @endforeach
            </td>
            <td class="px-4 py-3 text-right">{{ number_format($summary['guests'], 0, ',', '.') }}</td>
            <td class="px-4 py-3 text-right">{{ number_format($summary['photos'], 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
