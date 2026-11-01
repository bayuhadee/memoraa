<?php

namespace App\Http\Requests\Host;

use Illuminate\Foundation\Http\FormRequest;

/** Dipakai host & owner untuk hapus banyak foto sekaligus (berdasarkan UUID). */
class BulkDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // query selalu dibatasi ke event milik user
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return ['ids.required' => 'Pilih minimal 1 foto.'];
    }
}
