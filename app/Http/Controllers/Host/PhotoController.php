<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Http\Requests\Host\BulkDeleteRequest;
use App\Models\Photo;
use App\Services\PhotoUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PhotoController extends Controller
{
    public function __construct(private readonly PhotoUploadService $photos) {}

    public function index(Request $request)
    {
        $event = $request->user('host');

        return view('host.photos', [
            'event' => $event,
            'photos' => $event->photos()->visible()->with('guest:id,name')->latest('id')->paginate(48),
        ]);
    }

    public function destroy(Request $request, Photo $photo)
    {
        Gate::forUser($request->user('host'))->authorize('manageAsHost', $photo);

        $this->photos->delete($photo, 'host');

        return back()->with('success', 'Foto berhasil dihapus.');
    }

    public function bulkDestroy(BulkDeleteRequest $request)
    {
        // Query dibatasi ke event milik host: UUID event lain otomatis diabaikan.
        $targets = $request->user('host')->photos()->whereIn('uuid', $request->validated('ids'))->get();

        $count = $this->photos->deleteMany($targets, 'host');

        return back()->with('success', "{$count} foto berhasil dihapus.");
    }
}
