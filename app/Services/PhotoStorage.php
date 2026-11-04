<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Semua file disimpan di disk private "local" (storage/app/private),
 * di luar public/, dan hanya disajikan melalui MediaController.
 *
 * Struktur:
 *   tmp/{uuid}.upload            file asli menunggu diproses (dihapus setelah diproses)
 *   photos/{event_id}/{uuid}.jpg hasil resize
 *   thumbs/{event_id}/{uuid}.jpg thumbnail galeri
 *   zips/{event_id}/{uuid}.zip   arsip download host
 *   covers/{event_id}/{uuid}.jpg foto cover event (diatur owner)
 */
class PhotoStorage
{
    public const DISK = 'local';

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    public function path(string $relative): string
    {
        return $this->disk()->path($relative);
    }

    /** Simpan upload ke folder temporary dengan nama acak tanpa ekstensi dari user. */
    public function storeTemp(UploadedFile $file): string
    {
        return $this->disk()->putFileAs('tmp', $file, Str::uuid()->toString().'.upload');
    }

    public function newPhotoPaths(int $eventId): array
    {
        $name = Str::uuid()->toString().'.jpg';

        return ["photos/{$eventId}/{$name}", "thumbs/{$eventId}/{$name}"];
    }

    public function newCoverPath(int $eventId): string
    {
        return "covers/{$eventId}/".Str::uuid()->toString().'.jpg';
    }

    public function newZipPath(int $eventId, string $uuid): string
    {
        return "zips/{$eventId}/{$uuid}.zip";
    }

    public function put(string $relative, string $contents): void
    {
        $this->disk()->put($relative, $contents);
    }

    /** @param array<int, string|null> $paths */
    public function delete(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            $this->disk()->delete($paths);
        }
    }

    public function deleteEventDirectories(int $eventId): void
    {
        foreach (['photos', 'thumbs', 'zips'] as $dir) {
            $this->disk()->deleteDirectory("{$dir}/{$eventId}");
        }
    }
}
