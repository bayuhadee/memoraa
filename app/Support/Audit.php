<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/** Catatan audit (IP, user agent, waktu) ke storage/logs/audit-*.log. */
class Audit
{
    public static function log(string $action, array $context = []): void
    {
        $request = request();

        Log::channel('audit')->info($action, $context + [
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'at' => now()->toIso8601String(),
        ]);
    }
}
