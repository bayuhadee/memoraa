<?php

use App\Http\Middleware\EnsureHostSessionValid;
use App\Http\Middleware\EnsureUploadWindowOpen;
use App\Http\Middleware\IdentifyGuestDevice;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'guest.device' => IdentifyGuestDevice::class,
            'upload.open' => EnsureUploadWindowOpen::class,
            'host.session' => EnsureHostSessionValid::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        // Isi TRUSTED_PROXIES jika aplikasi berada di belakang reverse proxy / Cloudflare.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('owner*')
            ? route('owner.login')
            : route('host.login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->is('owner*')
            ? route('owner.dashboard')
            : route('host.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Upload melebihi post_max_size: PHP membuang seluruh body request.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $message = 'Ukuran file melebihi batas server ('.ini_get('post_max_size').'). Pilih foto yang lebih kecil atau upload satu per satu.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['photos' => [$message]]], 413);
            }

            // Session belum aktif di tahap ini, jadi tampilkan halaman error langsung.
            return response()->view('errors.413', ['message' => $message], 413);
        });
    })->create();
