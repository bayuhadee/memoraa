<?php

namespace App\Services;

use App\Exceptions\InvalidImageException;
use App\Exceptions\QuotaExceededException;
use App\Jobs\ProcessPhoto;
use App\Models\Guest;
use App\Models\Photo;
use App\Support\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PhotoUploadService
{
    public function __construct(
        private readonly PhotoStorage $storage,
        private readonly ImageInspector $inspector,
    ) {}

    public static function maxPerGuest(): int
    {
        return (int) config('galeri.photos.max_per_guest');
    }

    public function remainingQuota(Guest $guest): int
    {
        return max(0, self::maxPerGuest() - $guest->photos()->count());
    }

    /**
     * Upload beberapa foto sekaligus. Kuota dicek di dalam transaksi dengan
     * lockForUpdate pada baris guest, sehingga request paralel tetap
     * tidak bisa melewati batas.
     *
     * @param  list<UploadedFile>  $files
     * @param  array<int, string|null>  $captions
     * @return list<Photo>
     *
     * @throws QuotaExceededException|InvalidImageException
     */
    public function upload(Guest $guest, array $files, array $captions = []): array
    {
        // Lapisan pemeriksaan isi file (finfo, magic bytes, getimagesize, megapiksel).
        $inspected = [];
        foreach ($files as $i => $file) {
            $inspected[$i] = $this->inspector->inspect($file->getRealPath());
        }

        $tempPaths = [];

        try {
            $photos = DB::transaction(function () use ($guest, $files, $captions, $inspected, &$tempPaths) {
                $locked = Guest::whereKey($guest->id)->lockForUpdate()->firstOrFail();
                $current = $locked->photos()->count();
                $max = self::maxPerGuest();

                if ($current + count($files) > $max) {
                    $sisa = max(0, $max - $current);
                    throw new QuotaExceededException(
                        $sisa === 0
                            ? "Kuota foto Anda sudah penuh (maksimal {$max} foto). Hapus atau ganti foto yang ada."
                            : "Anda hanya bisa menambah {$sisa} foto lagi (maksimal {$max} foto)."
                    );
                }

                $photos = [];
                foreach ($files as $i => $file) {
                    $tempPaths[] = $temp = $this->storage->storeTemp($file);

                    $photo = new Photo(['caption' => $this->cleanCaption($captions[$i] ?? null)]);
                    $photo->event_id = $locked->event_id;
                    $photo->guest_id = $locked->id;
                    $photo->pending_path = $temp;
                    $photo->status = Photo::STATUS_PROCESSING;
                    $photo->original_name = $this->sanitizeName($file->getClientOriginalName());
                    $photo->mime = $inspected[$i]['mime'];
                    $photo->save();

                    $photos[] = $photo;
                }

                return $photos;
            });
        } catch (Throwable $e) {
            $this->storage->delete($tempPaths);
            throw $e;
        }

        foreach ($photos as $photo) {
            Audit::log('photo.uploaded', [
                'event_id' => $photo->event_id,
                'guest_id' => $photo->guest_id,
                'photo' => $photo->uuid,
                'original_name' => $photo->original_name,
                'mime' => $photo->mime,
            ]);
            ProcessPhoto::dispatch($photo->id);
        }

        return $photos;
    }

    /** Ganti file foto. File lama dihapus oleh job setelah file baru selesai diproses. */
    public function replace(Photo $photo, UploadedFile $file): Photo
    {
        if ($photo->isProcessing()) {
            throw new InvalidImageException('Foto ini masih diproses. Tunggu sebentar lalu coba lagi.');
        }

        $info = $this->inspector->inspect($file->getRealPath());
        $temp = $this->storage->storeTemp($file);

        $this->storage->delete([$photo->pending_path]);

        $photo->pending_path = $temp;
        $photo->status = Photo::STATUS_PROCESSING;
        $photo->original_name = $this->sanitizeName($file->getClientOriginalName());
        $photo->mime = $info['mime'];
        $photo->save();

        Audit::log('photo.replaced', [
            'event_id' => $photo->event_id,
            'guest_id' => $photo->guest_id,
            'photo' => $photo->uuid,
        ]);

        ProcessPhoto::dispatch($photo->id);

        return $photo;
    }

    public function updateCaption(Photo $photo, ?string $caption): Photo
    {
        $photo->caption = $this->cleanCaption($caption);
        $photo->save();

        return $photo;
    }

    public function delete(Photo $photo, string $by = 'guest'): void
    {
        Audit::log('photo.deleted', [
            'event_id' => $photo->event_id,
            'guest_id' => $photo->guest_id,
            'photo' => $photo->uuid,
            'by' => $by,
        ]);

        $photo->delete(); // file ikut terhapus via model event
    }

    /** @param iterable<Photo> $photos */
    public function deleteMany(iterable $photos, string $by): int
    {
        $count = 0;
        foreach ($photos as $photo) {
            $this->delete($photo, $by);
            $count++;
        }

        return $count;
    }

    private function cleanCaption(?string $caption): ?string
    {
        $caption = trim((string) $caption);

        return $caption === '' ? null : Str::limit($caption, (int) config('galeri.photos.caption_max'), '');
    }

    private function sanitizeName(?string $name): string
    {
        $base = pathinfo((string) $name, PATHINFO_FILENAME);
        $clean = preg_replace('/[^A-Za-z0-9._ -]/', '', $base) ?: 'foto';

        return Str::limit($clean, 150, '');
    }
}
