<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Jobs\BuildEventZip;
use App\Models\ZipExport;
use App\Services\PhotoStorage;
use App\Services\ZipService;
use Illuminate\Http\Request;

class ZipController extends Controller
{
    public function __construct(
        private readonly ZipService $zips,
        private readonly PhotoStorage $storage,
    ) {}

    public function index(Request $request)
    {
        $event = $request->user('host');

        return view('host.download', [
            'event' => $event,
            'photoCount' => $event->photos()->visible()->count(),
            'exports' => $event->zipExports()->latest()->limit(5)->get(),
            'syncMax' => (int) config('galeri.zip.sync_max_photos'),
        ]);
    }

    /**
     * Jumlah foto kecil: ZIP dibuat langsung & diunduh.
     * Jumlah besar: dibuat di queue, host menunggu lalu klik link download.
     */
    public function store(Request $request)
    {
        $event = $request->user('host');
        $count = $event->photos()->visible()->count();

        if ($count === 0) {
            return back()->with('error', 'Belum ada foto untuk diunduh.');
        }

        if ($count <= (int) config('galeri.zip.sync_max_photos')) {
            $export = $event->zipExports()->create(['status' => 'pending']);
            $result = $this->zips->build($event, $export->uuid);
            $export->update(['status' => 'ready', 'path' => $result['path'], 'file_count' => $result['count']]);

            return $this->send($export);
        }

        $export = $event->zipExports()->create(['status' => 'pending']);
        BuildEventZip::dispatch($export->id);

        return redirect()->route('host.zip.index')
            ->with('info', 'ZIP sedang disiapkan. Halaman ini akan memperbarui otomatis saat siap diunduh.');
    }

    public function status(Request $request, ZipExport $export)
    {
        abort_unless($export->event_id === $request->user('host')->id, 404);

        return response()->json(['status' => $export->status]);
    }

    public function download(Request $request, ZipExport $export)
    {
        abort_unless($export->event_id === $request->user('host')->id, 404);
        abort_unless($export->status === 'ready' && $export->path, 404);

        return $this->send($export);
    }

    private function send(ZipExport $export)
    {
        abort_unless($this->storage->disk()->exists($export->path), 404);

        return response()->download(
            $this->storage->path($export->path),
            $this->zips->downloadName($export->event),
            ['Content-Type' => 'application/zip'],
        );
    }
}
