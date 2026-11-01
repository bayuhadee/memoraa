<?php

namespace App\Rules;

use App\Exceptions\InvalidImageException;
use App\Services\ImageInspector;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/** Validasi isi file gambar (finfo, magic bytes, getimagesize, megapiksel, polyglot). */
class SafeImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return; // ditangani aturan "file"/"uploaded"
        }

        try {
            app(ImageInspector::class)->inspect($value->getRealPath());
        } catch (InvalidImageException $e) {
            $fail($e->getMessage());
        }
    }
}
