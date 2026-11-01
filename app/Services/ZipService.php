<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Photo;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ZipService
{
    public function __construct(private readonly PhotoStorage $storage) {}

    /**
     * Buat ZIP semua foto event. Nama file: {nama-tamu}_{nomor}.jpg.
     *
     * @return array{path: string, count: int} path relatif di disk private
     */
    public function build(Event $event, string $uuid): array
    {
        $relative = $this->storage->newZipPath($event->id, $uuid);
        $this->storage->disk()->makeDirectory(dirname($relative));
        $absolute = $this->storage->path($relative);

        $zip = new ZipArchive;
        if ($zip->open($absolute, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat file ZIP.');
        }

        $count = 0;
        $usedBases = []; // slug nama => guest_id (untuk membedakan tamu bernama sama)
        $numbers = [];   // guest_id => nomor foto terakhir

        $event->photos()->visible()->with('guest:id,name')->orderBy('guest_id')->orderBy('id')
            ->chunk(200, function ($photos) use ($zip, &$count, &$usedBases, &$numbers) {
                /** @var Photo $photo */
                foreach ($photos as $photo) {
                    $file = $this->storage->path($photo->path);
                    if (! is_file($file)) {
                        continue;
                    }

                    $base = Str::slug($photo->guest?->name ?? 'tamu') ?: 'tamu';
                    if (isset($usedBases[$base]) && $usedBases[$base] !== $photo->guest_id) {
                        $base .= '-'.$photo->guest_id;
                    }
                    $usedBases[$base] ??= $photo->guest_id;

                    $numbers[$photo->guest_id] = ($numbers[$photo->guest_id] ?? 0) + 1;
                    $entry = $base.'_'.$numbers[$photo->guest_id].'.jpg';

                    $zip->addFile($file, $entry);
                    // JPEG sudah terkompresi: simpan tanpa kompresi ulang agar cepat.
                    $zip->setCompressionName($entry, ZipArchive::CM_STORE);
                    $count++;
                }
            });

        if ($count === 0) {
            $zip->addFromString('BACA-SAYA.txt', 'Belum ada foto untuk event ini.');
        }

        $zip->close();

        return ['path' => $relative, 'count' => $count];
    }

    public function downloadName(Event $event): string
    {
        return 'foto-'.Str::slug($event->event_code).'-'.now()->format('Ymd-His').'.zip';
    }
}
