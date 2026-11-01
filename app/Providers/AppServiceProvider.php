<?php

namespace App\Providers;

use App\Models\Event;
use App\Support\GuestContext;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Konteks tamu baru untuk setiap request (aman untuk Octane/queue).
        $this->app->scoped(GuestContext::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        // Binding eksplisit: {event} selalu jadi model (berdasarkan event_code),
        // walau controller tidak meminta parameter Event.
        Route::model('event', Event::class);

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $device = fn (Request $request) => (string) $request->cookie(config('galeri.device_cookie.name'), 'none');

        $tooMany = fn (string $message) => function (Request $request, array $headers) use ($message) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 429, $headers);
            }

            return back()->with('error', $message)->withHeaders($headers);
        };

        // Buku tamu: per IP dan per perangkat.
        RateLimiter::for('guestbook', fn (Request $request) => [
            Limit::perMinute(10)->by('gb-ip:'.$request->ip())->response($tooMany('Terlalu banyak permintaan. Coba lagi sebentar.')),
            Limit::perMinute(5)->by('gb-dev:'.$device($request))->response($tooMany('Terlalu banyak permintaan. Coba lagi sebentar.')),
        ]);

        // Upload/ubah/hapus foto: per IP (banyak tamu bisa berbagi Wi-Fi gedung) dan per perangkat.
        RateLimiter::for('photo-upload', fn (Request $request) => [
            Limit::perMinute(60)->by('up-ip:'.$request->ip())->response($tooMany('Terlalu banyak upload dari jaringan ini. Coba lagi sebentar.')),
            Limit::perMinute(15)->by('up-dev:'.$device($request))->response($tooMany('Terlalu banyak upload. Tunggu sebentar lalu coba lagi.')),
        ]);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by('login:'.$request->ip()));

        RateLimiter::for('zip', fn (Request $request) => Limit::perMinute(3)->by('zip:'.$request->user('host')?->id.'|'.$request->ip()));

        RateLimiter::for('media', fn (Request $request) => Limit::perMinute(600)->by('media:'.$request->ip()));
    }
}
