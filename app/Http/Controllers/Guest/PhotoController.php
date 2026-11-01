<?php

namespace App\Http\Controllers\Guest;

use App\Exceptions\InvalidImageException;
use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\ReplacePhotoRequest;
use App\Http\Requests\Guest\UpdateCaptionRequest;
use App\Http\Requests\Guest\UploadPhotosRequest;
use App\Models\Event;
use App\Models\Photo;
use App\Services\PhotoUploadService;
use App\Support\GuestContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PhotoController extends Controller
{
    public function __construct(private readonly PhotoUploadService $uploads) {}

    public function store(UploadPhotosRequest $request, GuestContext $ctx)
    {
        if (! $ctx->guest) {
            return $this->fail($request, $ctx->event, 'Silakan isi buku tamu terlebih dahulu.', 'guest.guestbook');
        }

        try {
            $photos = $this->uploads->upload(
                $ctx->guest,
                array_values($request->file('photos', [])),
                array_values($request->input('captions', [])),
            );
        } catch (QuotaExceededException|InvalidImageException $e) {
            return $this->fail($request, $ctx->event, $e->getMessage());
        }

        return $this->done($request, $ctx->event, count($photos).' foto berhasil di-upload dan sedang diproses.');
    }

    public function replace(ReplacePhotoRequest $request, Event $event, Photo $photo, GuestContext $ctx)
    {
        Gate::forUser(null)->authorize('manageAsGuest', [$photo, $ctx->guest]);

        try {
            $this->uploads->replace($photo, $request->file('photo'));
        } catch (InvalidImageException $e) {
            return $this->fail($request, $event, $e->getMessage());
        }

        return $this->done($request, $event, 'Foto berhasil diganti dan sedang diproses.');
    }

    public function update(UpdateCaptionRequest $request, Event $event, Photo $photo, GuestContext $ctx)
    {
        Gate::forUser(null)->authorize('manageAsGuest', [$photo, $ctx->guest]);

        $this->uploads->updateCaption($photo, $request->validated('caption'));

        return $this->done($request, $event, 'Caption berhasil disimpan.');
    }

    public function destroy(Request $request, Event $event, Photo $photo, GuestContext $ctx)
    {
        Gate::forUser(null)->authorize('manageAsGuest', [$photo, $ctx->guest]);

        $this->uploads->delete($photo);

        return $this->done($request, $event, 'Foto berhasil dihapus.');
    }

    private function done(Request $request, Event $event, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'redirect' => route('guest.my-photos', $event)]);
        }

        return redirect()->route('guest.my-photos', $event)->with('success', $message);
    }

    private function fail(Request $request, Event $event, string $message, string $route = 'guest.my-photos')
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'errors' => ['photos' => [$message]]], 422);
        }

        return redirect()->route($route, $event)->with('error', $message);
    }
}
