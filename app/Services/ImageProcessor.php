<?php

namespace App\Services;

use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Resize + re-encode total. Re-encode membuang semua metadata (EXIF/GPS)
 * dan payload tersembunyi, karena hanya data piksel yang ditulis ulang.
 */
class ImageProcessor
{
    public function manager(): ImageManager
    {
        $options = [
            'autoOrientation' => true, // putar sesuai EXIF Orientation
            'decodeAnimation' => false, // ambil frame pertama saja
            'blendingColor' => 'ffffff', // latar putih untuk PNG transparan
            'strip' => true,
        ];

        $driver = config('galeri.image_driver', 'auto');
        if ($driver === 'auto') {
            $driver = extension_loaded('imagick') ? 'imagick' : 'gd';
        }

        return $driver === 'imagick' ? ImageManager::imagick(...$options) : ImageManager::gd(...$options);
    }

    /** Cover event: diperkecil proporsional (tanpa crop), di-encode ulang ke JPEG. */
    public function cover(string $sourcePath): string
    {
        $max = (int) config('galeri.cover.max_dimension');
        $image = $this->manager()->read($sourcePath)->scaleDown($max, $max);

        return (string) $image->encode(new JpegEncoder(quality: (int) config('galeri.cover.quality'), progressive: true, strip: true));
    }

    /**
     * @return array{full: string, thumb: string, width: int, height: int, orientation: string, mime: string}
     */
    public function process(string $sourcePath): array
    {
        $image = $this->manager()->read($sourcePath);

        $orientation = $image->width() >= $image->height() ? 'landscape' : 'portrait';
        [$w, $h] = config("galeri.photos.{$orientation}");

        // Crop proporsional (cover, tengah) ke ukuran tetap.
        $image->cover($w, $h, 'center');

        $encoder = new JpegEncoder(quality: (int) config('galeri.photos.quality'), progressive: true, strip: true);
        $full = (string) $image->encode($encoder);

        $image->scale(width: (int) config('galeri.photos.thumb_width'));
        $thumb = (string) $image->encode(new JpegEncoder(quality: 80, progressive: true, strip: true));

        return [
            'full' => $full,
            'thumb' => $thumb,
            'width' => $w,
            'height' => $h,
            'orientation' => $orientation,
            'mime' => 'image/jpeg',
        ];
    }
}
