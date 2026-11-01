<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Halaman error debug Laravel memakai inline script; jangan dipasang CSP di sana.
        $isDebugError = config('app.debug') && $response->getStatusCode() >= 500;

        if (! $isDebugError && ! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->csp());
        }

        return $response;
    }

    private function csp(): string
    {
        $self = "'self'";
        $script = [$self];
        $style = [$self];
        $connect = [$self];
        $img = [$self, 'data:', 'blob:', ...config('galeri.placeholder_img_hosts')];

        // Saat `npm run dev`, aset dimuat dari server Vite.
        if (Vite::isRunningHot()) {
            $hot = rtrim((string) file_get_contents(public_path('hot')));
            $ws = preg_replace('#^http#', 'ws', $hot);
            $script[] = $hot;
            $style[] = $hot;
            $style[] = "'unsafe-inline'";
            $connect[] = $hot;
            $connect[] = $ws;
            $img[] = $hot;
        }

        return implode('; ', [
            "default-src {$self}",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            // Atribut style (dipakai library lightbox) berisiko rendah; <style>/<script> inline tetap diblokir.
            "style-src-attr 'unsafe-inline'",
            'img-src '.implode(' ', $img),
            "font-src {$self} data:",
            'connect-src '.implode(' ', $connect),
            "object-src 'none'",
            "base-uri {$self}",
            "form-action {$self} https://wa.me https://api.whatsapp.com",
            "frame-ancestors 'none'",
        ]);
    }
}
