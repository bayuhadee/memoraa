<?php

namespace App\Http\Requests\Guest;

use App\Support\PhotoRules;
use Illuminate\Foundation\Http\FormRequest;

class ReplacePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // kepemilikan dicek via PhotoPolicy di controller
    }

    public function rules(): array
    {
        return ['photo' => PhotoRules::file()];
    }

    public function attributes(): array
    {
        return ['photo' => 'foto'];
    }
}
