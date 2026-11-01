<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OwnerLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $key = 'owner-login:'.Str::lower($this->string('email')).'|'.$this->ip();
        $ipKey = 'owner-login-ip:'.$this->ip();

        foreach ([$key => 5, $ipKey => 20] as $k => $max) {
            if (RateLimiter::tooManyAttempts($k, $max)) {
                $minutes = (int) ceil(RateLimiter::availableIn($k) / 60);
                throw ValidationException::withMessages([
                    'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.",
                ]);
            }
        }

        if (! Auth::guard('owner')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 3600);

            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        RateLimiter::clear($key);
    }
}
