<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Photo;
use Illuminate\Contracts\Auth\Authenticatable;

class PhotoPolicy
{
    /**
     * Tamu (tanpa login) hanya boleh mengelola foto miliknya sendiri,
     * yaitu foto dengan guest_id dari device token perangkat ini, di event yang sama.
     */
    public function manageAsGuest(?Authenticatable $user, Photo $photo, ?Guest $guest): bool
    {
        return $guest !== null
            && $photo->guest_id === $guest->id
            && $photo->event_id === $guest->event_id;
    }

    /** Host hanya boleh mengelola foto event miliknya. */
    public function manageAsHost(Authenticatable $user, Photo $photo): bool
    {
        return $user instanceof Event && $photo->event_id === $user->id;
    }
}
