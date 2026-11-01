<?php

namespace App\Services;

use App\Exceptions\InvalidImageException;
use finfo;
use Imagick;
use Throwable;

/**
 * Pemeriksaan keamanan file gambar berdasarkan ISI file,
 * bukan nama/ekstensi/header dari browser:
 *  - MIME asli via finfo
 *  - magic bytes
 *  - getimagesize() (atau Imagick ping untuk HEIC)
 *  - batas megapiksel (anti decompression bomb)
 *  - dimensi minimal
 *  - tolak GIF animasi & file yang menyisipkan kode (polyglot)
 */
class ImageInspector
{
    /** MIME asli => ekstensi yang diizinkan. SVG sengaja tidak termasuk. */
    private const BASE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const HEIF_MIMES = [
        'image/heic' => 'heic',
        'image/heif' => 'heif',
    ];

    private const HEIF_BRANDS = ['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'mif1', 'msf1'];

    /** Pola yang tidak mungkin ada di gambar normal. */
    private const SUSPICIOUS_PATTERNS = ['<?php', '<script', '__halt_compiler'];

    public static function heifSupported(): bool
    {
        if (! extension_loaded('imagick') || config('galeri.image_driver') === 'gd') {
            return false;
        }

        try {
            return count(Imagick::queryFormats('HEIC')) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<string, string> */
    public static function allowedMimes(): array
    {
        return self::heifSupported() ? self::BASE_MIMES + self::HEIF_MIMES : self::BASE_MIMES;
    }

    /** @return list<string> ekstensi untuk aturan "mimes" Laravel */
    public static function allowedExtensions(): array
    {
        $ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        return self::heifSupported() ? [...$ext, 'heic', 'heif'] : $ext;
    }

    /**
     * @return array{mime: string, width: int, height: int}
     *
     * @throws InvalidImageException
     */
    public function inspect(string $path): array
    {
        if (! is_file($path) || ! is_readable($path) || filesize($path) === 0) {
            throw new InvalidImageException('File tidak dapat dibaca. Silakan pilih ulang foto Anda.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';

        if (! array_key_exists($mime, self::allowedMimes())) {
            throw new InvalidImageException('Format file tidak didukung. Gunakan foto JPG, PNG, WEBP'
                .(self::heifSupported() ? ', HEIC' : '').' atau GIF.');
        }

        $head = (string) file_get_contents($path, false, null, 0, 32);

        if (! $this->magicBytesMatch($mime, $head)) {
            throw new InvalidImageException('Isi file tidak sesuai dengan format gambar.');
        }

        [$width, $height] = $this->dimensions($path, $mime);

        $maxPixels = (int) config('galeri.photos.max_megapixels') * 1_000_000;
        if ($width * $height > $maxPixels) {
            throw new InvalidImageException('Resolusi foto terlalu besar (maksimal '
                .config('galeri.photos.max_megapixels').' megapiksel).');
        }

        $min = (int) config('galeri.photos.min_dimension');
        if ($width < $min || $height < $min) {
            throw new InvalidImageException("Foto terlalu kecil. Minimal {$min} × {$min} piksel.");
        }

        $this->scanContents($path, $mime);

        return ['mime' => $mime, 'width' => $width, 'height' => $height];
    }

    private function magicBytesMatch(string $mime, string $head): bool
    {
        return match ($mime) {
            'image/jpeg' => str_starts_with($head, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($head, "\x89PNG\r\n\x1A\n"),
            'image/gif' => str_starts_with($head, 'GIF87a') || str_starts_with($head, 'GIF89a'),
            'image/webp' => str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP',
            'image/heic', 'image/heif' => substr($head, 4, 4) === 'ftyp'
                && in_array(substr($head, 8, 4), self::HEIF_BRANDS, true),
            default => false,
        };
    }

    /** @return array{0: int, 1: int} */
    private function dimensions(string $path, string $mime): array
    {
        if (isset(self::HEIF_MIMES[$mime])) {
            try {
                $img = new Imagick;
                $img->pingImage($path);

                return [$img->getImageWidth(), $img->getImageHeight()];
            } catch (Throwable) {
                throw new InvalidImageException('File HEIC tidak dapat dibaca sebagai gambar.');
            }
        }

        $info = @getimagesize($path);

        if ($info === false || empty($info[0]) || empty($info[1]) || ($info['mime'] ?? '') !== $mime) {
            throw new InvalidImageException('File tidak dapat dibaca sebagai gambar.');
        }

        return [(int) $info[0], (int) $info[1]];
    }

    /**
     * Baca file per potongan (hemat memori untuk file besar):
     * tolak kode tersisip dan GIF animasi.
     */
    private function scanContents(string $path, string $mime): void
    {
        $handle = fopen($path, 'rb');
        $carry = '';
        $gifFrames = 0;

        try {
            while (! feof($handle)) {
                $chunk = $carry.fread($handle, 1024 * 1024);
                $lower = strtolower($chunk);

                foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
                    if (str_contains($lower, $pattern)) {
                        throw new InvalidImageException('File ditolak karena mengandung data yang tidak aman.');
                    }
                }

                if ($mime === 'image/gif') {
                    // Graphic Control Extension (muncul sekali per frame), diikuti
                    // terminator blok lalu image descriptor / extension berikutnya.
                    preg_match_all('/\x21\xF9\x04.{4}\x00[\x2C\x21]/s', $chunk, $m, PREG_OFFSET_CAPTURE);
                    foreach ($m[0] as [, $offset]) {
                        // Lewati match yang seluruhnya ada di sisa potongan sebelumnya (sudah dihitung).
                        if ($offset + 9 > strlen($carry)) {
                            $gifFrames++;
                        }
                    }
                    if ($gifFrames > 1) {
                        throw new InvalidImageException('GIF animasi tidak didukung. Gunakan GIF statis atau foto biasa.');
                    }
                }

                $carry = substr($chunk, -16);
            }
        } finally {
            fclose($handle);
        }
    }
}
