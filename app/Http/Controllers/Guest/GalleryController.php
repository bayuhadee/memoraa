<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Support\GuestContext;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function show(Request $request, GuestContext $ctx)
    {
        $event = $ctx->event;

        $photos = $event->photos()->visible()
            ->with('guest:id,name')
            ->latest('id')
            ->paginate(config('galeri.photos.per_page'));

        // Infinite scroll: kembalikan potongan HTML halaman berikutnya.
        if ($request->ajax() && $request->has('page')) {
            return response()->json([
                'html' => view('guest.partials.photo-items', ['photos' => $photos])->render(),
                'next' => $photos->nextPageUrl(),
            ]);
        }

        $wishes = $event->guests()->whereNotNull('message')->latest()->limit(100)->get(['id', 'name', 'message', 'created_at']);

        return view('guest.gallery', [
            'event' => $event,
            'photos' => $photos,
            'wishes' => $wishes,
            'guestCount' => $event->guests()->count(),
            'hasGuest' => $ctx->hasGuest(),
            'uploadOpen' => $event->isUploadOpen(),
        ]);
    }
}
