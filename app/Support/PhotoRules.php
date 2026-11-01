<?php

namespace App\Support;

use App\Rules\SafeImage;
use App\Services\ImageInspector;

/**
 * Aturan validasi file foto. Sengaja TANPA batas ukuran file (aturan "max"),
 * sesuai kebutuhan; batas fisik ditentukan php.ini & web server.
 */
class PhotoRules
{
    /** @return list<mixed> */
    public static function file(): array
    {
        $rules = [
            'required',
            'file',
            'mimes:'.implode(',', ImageInspector::allowedExtensions()),
            'mimetypes:'.implode(',', array_keys(ImageInspector::allowedMimes())),
        ];

        // Aturan bawaan "image" & "dimensions" memakai getimagesize(),
        // yang tidak mengenali HEIC; untuk HEIC dimensi dicek oleh SafeImage.
        if (! ImageInspector::heifSupported()) {
            $min = (int) config('galeri.photos.min_dimension');
            $rules[] = 'image';
            $rules[] = "dimensions:min_width={$min},min_height={$min}";
        }

        $rules[] = new SafeImage;

        return $rules;
    }

    /** Atribut accept untuk input file di HTML. */
    public static function accept(): string
    {
        $accept = 'image/jpeg,image/png,image/webp,image/gif';

        return ImageInspector::heifSupported() ? $accept.',image/heic,image/heif,.heic,.heif' : $accept;
    }
}
