<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Models\ZipExport;
use App\Services\PhotoStorage;
use Illuminate\Console\Command;

class CleanupPhotos extends Command
{
    protected $signature = 'photos:cleanup {--hours=24 : Umur minimum file temporary (jam)}';

    protected $description = 'Hapus file upload temporary & ZIP lama yang tertinggal';

    public function handle(PhotoStorage $storage): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);
        $disk = $storage->disk();

        // 1) Foto yang macet di status "processing" terlalu lama.
        $stuck = 0;
        Photo::where('status', Photo::STATUS_PROCESSING)->where('updated_at', '<', $cutoff)
            ->each(function (Photo $photo) use ($storage, &$stuck) {
                $storage->delete([$photo->pending_path]);
                $photo->forceFill([
                    'pending_path' => null,
                    'status' => $photo->path ? Photo::STATUS_READY : Photo::STATUS_FAILED,
                ])->save();
                $stuck++;
            });

        // 2) File temporary yang tidak lagi dirujuk oleh foto mana pun.
        $referenced = Photo::whereNotNull('pending_path')->pluck('pending_path')->flip();
        $tmp = 0;
        foreach ($disk->files('tmp') as $file) {
            if (! isset($referenced[$file]) && $disk->lastModified($file) < $cutoff->getTimestamp()) {
                $disk->delete($file);
                $tmp++;
            }
        }

        // 3) ZIP download lama.
        $zips = 0;
        ZipExport::where('created_at', '<', now()->subHours((int) config('galeri.zip.keep_hours')))
            ->each(function (ZipExport $zip) use (&$zips) {
                $zip->delete(); // file ikut terhapus
                $zips++;
            });

        $this->info("Selesai: {$tmp} file temporary, {$zips} ZIP lama, {$stuck} foto macet dibersihkan.");

        return self::SUCCESS;
    }
}
