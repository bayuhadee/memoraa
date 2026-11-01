<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Host\BulkDeleteRequest;
use App\Http\Requests\Owner\ResetPhotosRequest;
use App\Models\Event;
use App\Models\Photo;
use App\Services\EventService;
use App\Services\PhotoUploadService;

class EventPhotoController extends Controller
{
    public function __construct(private readonly PhotoUploadService $photos) {}

    public function destroy(Event $event, Photo $photo)
    {
        abort_unless($photo->event_id === $event->id, 404);

        $this->photos->delete($photo, 'owner');

        return back()->with('success', 'Foto berhasil dihapus.');
    }

    public function bulkDestroy(BulkDeleteRequest $request, Event $event)
    {
        $count = $this->photos->deleteMany(
            $event->photos()->whereIn('uuid', $request->validated('ids'))->get(),
            'owner',
        );

        return back()->with('success', "{$count} foto berhasil dihapus.");
    }

    public function reset(ResetPhotosRequest $request, Event $event, EventService $events)
    {
        $count = $events->resetPhotos($event);

        return back()->with('success', "Reset selesai: {$count} foto dihapus dari event {$event->event_code}.");
    }
}
