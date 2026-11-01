<?php

namespace App\Http\Requests\Host;

use App\Services\HostAuthService;
use Illuminate\Foundation\Http\FormRequest;

class HostLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_code' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return ['event_code' => 'ID event', 'password' => 'password'];
    }

    public function authenticate(HostAuthService $auth): void
    {
        $auth->attempt($this, $this->string('event_code'), $this->string('password'));
    }
}
