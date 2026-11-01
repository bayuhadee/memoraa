<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $event = $request->user('host');

        return view('host.dashboard', [
            'event' => $event,
            'guestCount' => $event->guests()->count(),
            'photoCount' => $event->photos()->visible()->count(),
            'wishCount' => $event->guests()->whereNotNull('message')->count(),
            'latestPhotos' => $event->photos()->visible()->with('guest:id,name')->latest('id')->limit(8)->get(),
        ]);
    }

    public function qrcode(Request $request, QrCodeService $qr)
    {
        $event = $request->user('host');
        $png = $qr->png($event->galleryUrl(), $event->bride_groom_names);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline')
                .'; filename="qr-'.$event->event_code.'.png"',
        ]);
    }
}
