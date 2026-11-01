<?php

namespace App\Http\Middleware;

use App\Support\GuestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Semua aksi tambah/ubah/hapus buku tamu & foto hanya boleh
 * sampai 23:59:59 pada tanggal acara (zona waktu config galeri.timezone).
 */
class EnsureUploadWindowOpen
{
    public const MESSAGE = 'Periode upload & pengisian buku tamu telah berakhir.';

    public function __construct(private readonly GuestContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $event = $this->context->event;

        if (! $event || ! $event->isUploadOpen()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => self::MESSAGE], 403);
            }

            return redirect()->route('guest.gallery', $event ?? abort(404))->with('error', self::MESSAGE);
        }

        return $next($request);
    }
}
