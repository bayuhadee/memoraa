<?php

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['caption' => ['nullable', 'string', 'max:'.config('galeri.photos.caption_max')]];
    }
}
