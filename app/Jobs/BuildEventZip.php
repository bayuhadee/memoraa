<?php

namespace App\Jobs;

use App\Models\ZipExport;
use App\Services\ZipService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class BuildEventZip implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public int $zipExportId) {}

    public function handle(ZipService $zips): void
    {
        $export = ZipExport::with('event')->find($this->zipExportId);
        if (! $export || ! $export->event) {
            return;
        }

        $result = $zips->build($export->event, $export->uuid);

        $export->update([
            'status' => 'ready',
            'path' => $result['path'],
            'file_count' => $result['count'],
        ]);
    }

    public function failed(Throwable $e): void
    {
        ZipExport::whereKey($this->zipExportId)->update(['status' => 'failed']);
    }
}
