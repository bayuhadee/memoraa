<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @param  array{from?: ?string, to?: ?string, status?: ?string}  $filters
     */
    public function query(array $filters): Builder
    {
        return Event::query()
            ->withCount(['guests', 'photos'])
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('event_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('event_date', '<=', $to))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('payment_status', $status))
            ->orderBy('event_date')
            ->orderBy('id');
    }

    /** @return array{events: int, guests: int, photos: int, by_status: array<string, int>} */
    public function summary(Collection $events): array
    {
        $byStatus = [];
        foreach ($events as $event) {
            $key = $event->payment_status->label();
            $byStatus[$key] = ($byStatus[$key] ?? 0) + 1;
        }

        return [
            'events' => $events->count(),
            'guests' => (int) $events->sum('guests_count'),
            'photos' => (int) $events->sum('photos_count'),
            'by_status' => $byStatus,
        ];
    }
}
