<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Photo;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now(config('galeri.timezone'))->toDateString();

        $statusCounts = Event::query()
            ->selectRaw('payment_status, count(*) as total')
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status');

        return view('owner.dashboard', [
            'totalEvents' => Event::count(),
            'upcomingCount' => Event::whereDate('event_date', '>=', $today)->count(),
            'totalPhotos' => Photo::visible()->count(),
            'statusCounts' => collect(PaymentStatus::cases())
                ->mapWithKeys(fn (PaymentStatus $s) => [$s->label() => (int) ($statusCounts[$s->value] ?? 0)]),
            'upcoming' => Event::withCount(['guests', 'photos'])
                ->whereDate('event_date', '>=', $today)->orderBy('event_date')->limit(8)->get(),
        ]);
    }
}
