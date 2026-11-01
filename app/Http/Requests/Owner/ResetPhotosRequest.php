<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class ResetPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['confirm_code' => ['required', 'string']];
    }

    public function after(): array
    {
        return [function ($validator) {
            $typed = strtoupper(trim((string) $this->input('confirm_code')));
            if ($typed !== $this->route('event')->event_code) {
                $validator->errors()->add('confirm_code', 'ID event yang diketik tidak cocok.');
            }
        }];
    }
}
