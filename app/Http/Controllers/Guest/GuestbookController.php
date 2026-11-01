<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\GuestbookRequest;
use App\Services\GuestbookService;
use App\Support\GuestContext;
use Illuminate\Support\Facades\Gate;

class GuestbookController extends Controller
{
    public function __construct(private readonly GuestbookService $guestbook) {}

    public function edit(GuestContext $ctx)
    {
        if (! $ctx->event->isUploadOpen()) {
            return redirect()->route('guest.gallery', $ctx->event)
                ->with('error', 'Periode pengisian buku tamu telah berakhir.');
        }

        return view('guest.guestbook', ['event' => $ctx->event, 'guest' => $ctx->guest]);
    }

    public function store(GuestbookRequest $request, GuestContext $ctx)
    {
        [, $created] = $this->guestbook->create($ctx->event, $ctx->deviceToken, $request->guestData(), $request);

        return redirect()->route('guest.my-photos', $ctx->event)->with(
            $created ? 'success' : 'info',
            $created
                ? 'Terima kasih! Buku tamu tersimpan. Sekarang upload foto terbaik Anda.'
                : 'Perangkat ini sudah mengisi buku tamu. Anda bisa mengubahnya kapan saja sebelum acara berakhir.'
        );
    }

    public function update(GuestbookRequest $request, GuestContext $ctx)
    {
        if (! $ctx->guest) {
            return redirect()->route('guest.guestbook', $ctx->event);
        }

        Gate::forUser(null)->authorize('updateAsGuest', [$ctx->guest, $ctx->deviceToken]);

        $this->guestbook->update($ctx->guest, $request->guestData());

        return redirect()->route('guest.my-photos', $ctx->event)->with('success', 'Buku tamu berhasil diperbarui.');
    }
}
