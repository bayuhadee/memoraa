<?php

namespace App\Http\Middleware;

use App\Services\HostAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keluarkan host jika event dinonaktifkan atau password event diganti owner.
 * (Event yang dihapus otomatis tidak bisa login karena soft delete.)
 */
class EnsureHostSessionValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $event = Auth::guard('host')->user();

        $valid = $event
            && $event->is_active
            && hash_equals(
                (string) $request->session()->get(HostAuthService::SESSION_SIGNATURE),
                HostAuthService::signature($event),
            );

        if (! $valid) {
            Auth::guard('host')->logout();
            $request->session()->forget(HostAuthService::SESSION_SIGNATURE);
            $request->session()->regenerateToken();

            return redirect()->route('host.login')
                ->with('error', 'Sesi Anda berakhir. Silakan login kembali.');
        }

        return $next($request);
    }
}
