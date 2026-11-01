<?php

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;

/** Dipakai untuk simpan (POST) maupun ubah (PUT) buku tamu. */
class GuestbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // identitas tamu & jendela upload dicek middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            // Honeypot: tidak terlihat oleh manusia, biasanya diisi bot.
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return ['website.prohibited' => 'Permintaan tidak valid.'];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'address' => 'alamat', 'message' => 'ucapan'];
    }

    /** @return array{name: string, address: string, message: string} */
    public function guestData(): array
    {
        return [
            'name' => trim($this->string('name')),
            'address' => trim($this->string('address')),
            'message' => trim($this->string('message')),
        ];
    }
}
