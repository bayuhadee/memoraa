<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Services\ImageProcessor;
use App\Services\PhotoStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Exceptions\DecoderException;
use Throwable;

/**
 * Auto-orient, crop ke 1200×800 / 800×1200, buat thumbnail,
 * re-encode JPEG tanpa metadata, lalu hapus file asli.
 */
class ProcessPhoto implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $photoId) {}

    public function handle(ImageProcessor $processor, PhotoStorage $storage): void
    {
        $photo = Photo::find($this->photoId);

        if (! $photo || ! $photo->pending_path) {
            return; // foto sudah dihapus / sudah diproses
        }

        $source = $storage->path($photo->pending_path);

        if (! is_file($source)) {
            $this->markFailed($photo, $storage, 'temp file missing');

            return;
        }

        // Gambar besar (hingga 50 MP) butuh memori ekstra saat di-decode.
        @ini_set('memory_limit', '1024M');

        try {
            $result = $processor->process($source);
        } catch (DecoderException $e) {
            $this->markFailed($photo, $storage, $e->getMessage());

            return;
        }

        [$fullPath, $thumbPath] = $storage->newPhotoPaths($photo->event_id);
        $storage->put($fullPath, $result['full']);
        $storage->put($thumbPath, $result['thumb']);

        $old = [$photo->path, $photo->thumb_path, $photo->pending_path];

        $photo->forceFill([
            'path' => $fullPath,
            'thumb_path' => $thumbPath,
            'pending_path' => null,
            'status' => Photo::STATUS_READY,
            'orientation' => $result['orientation'],
            'width' => $result['width'],
            'height' => $result['height'],
            'file_size' => strlen($result['full']),
            'mime' => $result['mime'],
        ]);

        // Foto bisa saja dihapus saat job berjalan.
        if (! Photo::whereKey($photo->id)->exists()) {
            $storage->delete([$fullPath, $thumbPath, ...$old]);

            return;
        }

        $photo->save();

        // File asli & versi lama (jika ganti foto) tidak disimpan.
        $storage->delete($old);
    }

    public function failed(Throwable $e): void
    {
        if ($photo = Photo::find($this->photoId)) {
            $this->markFailed($photo, app(PhotoStorage::class), $e->getMessage());
        }
    }

    private function markFailed(Photo $photo, PhotoStorage $storage, string $reason): void
    {
        Log::warning('Gagal memproses foto', ['photo' => $photo->uuid, 'reason' => $reason]);

        $storage->delete([$photo->pending_path]);

        // Jika ini proses "ganti foto", foto lama tetap dipakai.
        $photo->forceFill([
            'pending_path' => null,
            'status' => $photo->path ? Photo::STATUS_READY : Photo::STATUS_FAILED,
        ])->save();
    }
}
