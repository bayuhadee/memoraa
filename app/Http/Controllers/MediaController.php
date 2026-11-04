<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Photo;
use App\Services\PhotoStorage;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Menyajikan foto dari storage private. Akses berdasarkan UUID foto
 * (tidak bisa ditebak) — sama seperti siapa pun yang memegang link event.
 */
class MediaController extends Controller
{
    public function __construct(private readonly PhotoStorage $storage) {}

    public function show(Photo $photo, string $size): BinaryFileResponse
    {
        $this->ensureAccessible($photo);

        $path = $size === 'thumb' ? $photo->thumb_path : $photo->path;

        return $this->file($path, 'inline', $photo->downloadName($this->number($photo)));
    }

    public function download(Photo $photo): BinaryFileResponse
    {
        $this->ensureAccessible($photo);

        return $this->file($photo->path, 'attachment', $photo->downloadName($this->number($photo)));
    }

    public function cover(Event $event): BinaryFileResponse
    {
        $this->ensureEventAccessible($event);

        return $this->file($event->cover_path, 'inline', 'cover-'.$event->event_code.'.jpg');
    }

    private function ensureAccessible(Photo $photo): void
    {
        abort_unless($photo->hasImage(), 404);

        $event = $photo->event;
        abort_if($event === null, 404);

        $this->ensureEventAccessible($event);
    }

    private function ensureEventAccessible(Event $event): void
    {
        // Event nonaktif: hanya owner / host event tersebut yang masih bisa melihat.
        if (! $event->is_active) {
            $isOwner = Auth::guard('owner')->check();
            $isHost = Auth::guard('host')->id() === $event->id;
            abort_unless($isOwner || $isHost, 404);
        }
    }

    /** Nomor urut foto milik tamu tersebut (1 atau 2). */
    private function number(Photo $photo): int
    {
        return Photo::where('guest_id', $photo->guest_id)->where('id', '<=', $photo->id)->count();
    }

    private function file(?string $relative, string $disposition, string $name): BinaryFileResponse
    {
        abort_if($relative === null || ! $this->storage->disk()->exists($relative), 404);

        $response = response()->file($this->storage->path($relative), [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=604800',
        ]);

        $response->setContentDisposition(
            $disposition === 'attachment' ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $name,
        );

        return $response;
    }
}
