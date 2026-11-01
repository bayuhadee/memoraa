<?php

namespace App\Services;

use App\Models\Event;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Login host dengan event_code + password 4 karakter.
 * Karena ruang password kecil, dipakai 3 lapis pembatas:
 *  - 5 percobaan / menit per IP
 *  - 10 gagal / 15 menit per event_code (melindungi dari serangan banyak IP)
 *  - 20 gagal / jam per IP -> kunci 1 jam
 */
class HostAuthService
{
    public const SESSION_SIGNATURE = 'host_pw_sig';

    public function attempt(Request $request, string $code, string $password): void
    {
        $code = strtoupper(trim($code));
        $password = strtoupper(trim($password));
        $ip = (string) $request->ip();

        $limits = [
            ['key' => "host-login:ip:{$ip}", 'max' => 5, 'decay' => 60],
            ['key' => 'host-login:code:'.sha1($code), 'max' => 10, 'decay' => 900],
            ['key' => "host-login:ip-hour:{$ip}", 'max' => 20, 'decay' => 3600],
        ];

        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($limit['key'], $limit['max'])) {
                $minutes = (int) ceil(RateLimiter::availableIn($limit['key']) / 60);
                throw ValidationException::withMessages([
                    'event_code' => "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.",
                ]);
            }
        }

        $ok = Auth::guard('host')->attemptWhen(
            ['event_code' => $code, 'password' => $password],
            fn (Event $event) => $event->is_active,
        );

        if (! $ok) {
            foreach ($limits as $limit) {
                RateLimiter::hit($limit['key'], $limit['decay']);
            }

            Audit::log('host.login_failed', ['event_code' => $code]);

            throw ValidationException::withMessages([
                'event_code' => 'ID event atau password salah.',
            ]);
        }

        RateLimiter::clear($limits[0]['key']);

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_SIGNATURE, self::signature(Auth::guard('host')->user()));

        Audit::log('host.login', ['event_id' => Auth::guard('host')->id()]);
    }

    /** Tanda tangan password saat login; berubah jika owner generate ulang password. */
    public static function signature(Event $event): string
    {
        return hash_hmac('sha256', (string) $event->password_hash, (string) config('app.key'));
    }
}
