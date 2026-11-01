<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GuestbookController extends Controller
{
    public function index(Request $request)
    {
        $event = $request->user('host');

        return view('host.guestbook', [
            'event' => $event,
            'guests' => $event->guests()->withCount('photos')->latest()->paginate(50),
        ]);
    }

    /** Hapus ucapan (data tamu & fotonya tetap ada). */
    public function destroy(Request $request, Guest $guest)
    {
        Gate::forUser($request->user('host'))->authorize('manageAsHost', $guest);

        $guest->forceFill(['message' => null])->save();
        Audit::log('guestbook.message_deleted', ['event_id' => $guest->event_id, 'guest_id' => $guest->id]);

        return back()->with('success', 'Ucapan berhasil dihapus.');
    }
}
