<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Guest;

/**
 * Konteks tamu untuk request saat ini, diisi oleh middleware IdentifyGuestDevice.
 * Terdaftar sebagai scoped binding (baru setiap request).
 */
class GuestContext
{
    public ?Event $event = null;

    public ?string $deviceToken = null;

    public ?Guest $guest = null;

    public function hasGuest(): bool
    {
        return $this->guest !== null;
    }
}
