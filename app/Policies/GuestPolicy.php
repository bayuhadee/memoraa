<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Contracts\Auth\Authenticatable;

class GuestPolicy
{
    /** Tamu hanya boleh mengubah entri buku tamu milik perangkatnya. */
    public function updateAsGuest(?Authenticatable $user, Guest $guest, ?string $deviceToken): bool
    {
        return $deviceToken !== null && hash_equals($guest->device_token, $deviceToken);
    }

    /** Host hanya boleh menghapus ucapan di event miliknya. */
    public function manageAsHost(Authenticatable $user, Guest $guest): bool
    {
        return $user instanceof Event && $guest->event_id === $user->id;
    }
}
