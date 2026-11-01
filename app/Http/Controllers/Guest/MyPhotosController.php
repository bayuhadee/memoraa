<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Services\PhotoUploadService;
use App\Support\GuestContext;
use App\Support\PhotoRules;

class MyPhotosController extends Controller
{
    public function index(GuestContext $ctx, PhotoUploadService $uploads)
    {
        if (! $ctx->guest) {
            return redirect()->route('guest.guestbook', $ctx->event)
                ->with('info', 'Silakan isi buku tamu terlebih dahulu.');
        }

        return view('guest.my-photos', [
            'event' => $ctx->event,
            'guest' => $ctx->guest,
            'photos' => $ctx->guest->photos()->oldest('id')->get(),
            'remaining' => $uploads->remainingQuota($ctx->guest),
            'max' => PhotoUploadService::maxPerGuest(),
            'uploadOpen' => $ctx->event->isUploadOpen(),
            'accept' => PhotoRules::accept(),
        ]);
    }

    /** Dipakai halaman "Foto Saya" untuk menunggu proses resize selesai. */
    public function status(GuestContext $ctx)
    {
        $processing = $ctx->guest
            ? $ctx->guest->photos()->where('status', Photo::STATUS_PROCESSING)->count()
            : 0;

        return response()->json(['processing' => $processing]);
    }
}
