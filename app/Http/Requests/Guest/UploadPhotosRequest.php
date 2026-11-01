<?php

namespace App\Http\Requests\Guest;

use App\Support\PhotoRules;
use Illuminate\Foundation\Http\FormRequest;

class UploadPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = (int) config('galeri.photos.max_per_guest');

        return [
            'photos' => ['required', 'array', 'min:1', "max:{$max}"],
            'photos.*' => PhotoRules::file(),
            'captions' => ['nullable', 'array', "max:{$max}"],
            'captions.*' => ['nullable', 'string', 'max:'.config('galeri.photos.caption_max')],
        ];
    }

    public function messages(): array
    {
        return [
            'photos.required' => 'Pilih minimal 1 foto untuk di-upload.',
            'photos.max' => 'Maksimal :max foto per tamu.',
        ];
    }

    public function attributes(): array
    {
        return ['photos.*' => 'foto', 'captions.*' => 'caption'];
    }
}
