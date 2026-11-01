<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Models\Guest;
use App\Support\GuestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menentukan event aktif + identitas perangkat tamu.
 *
 * Token perangkat disimpan di cookie jangka panjang (1 tahun) yang
 * dienkripsi oleh EncryptCookies, HttpOnly, SameSite=Lax, dan Secure
 * saat diakses lewat HTTPS. Session Laravel tidak dipakai karena bisa kedaluwarsa.
 */
class IdentifyGuestDevice
{
    public function __construct(private readonly GuestContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $event = $request->route('event');

        if (! $event instanceof Event || ! $event->is_active) {
            abort(404);
        }

        $cookie = config('galeri.device_cookie.name');
        $token = $request->cookie($cookie);

        $isNew = ! is_string($token) || ! preg_match('/^[A-Za-z0-9]{64}$/', $token);
        if ($isNew) {
            $token = Str::random(64);
        }

        $this->context->event = $event;
        $this->context->deviceToken = $token;
        $this->context->guest = Guest::where('event_id', $event->id)->where('device_token', $token)->first();

        $response = $next($request);

        if ($isNew) {
            $response->headers->setCookie(Cookie::make(
                name: $cookie,
                value: $token,
                minutes: (int) config('galeri.device_cookie.minutes'),
                path: '/',
                secure: config('session.secure') ?? $request->isSecure(),
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return $response;
    }
}
