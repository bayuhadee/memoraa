<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Support\Audit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class GuestbookService
{
    /**
     * Buat entri buku tamu. Jika perangkat ini sudah punya entri
     * (unique event_id + device_token), entri lama dikembalikan apa adanya.
     *
     * @param  array{name: string, address: string, message: string}  $data
     * @return array{0: Guest, 1: bool} [guest, dibuat baru?]
     */
    public function create(Event $event, string $deviceToken, array $data, Request $request): array
    {
        $existing = $this->find($event, $deviceToken);
        if ($existing) {
            return [$existing, false];
        }

        $guest = new Guest($data);
        $guest->event_id = $event->id;
        $guest->device_token = $deviceToken;
        $guest->ip_address = $request->ip();
        $guest->user_agent = substr((string) $request->userAgent(), 0, 1000);

        try {
            $guest->save();
        } catch (UniqueConstraintViolationException) {
            // Dua request bersamaan dari perangkat yang sama: DB menolak yang kedua.
            return [$this->find($event, $deviceToken), false];
        }

        Audit::log('guestbook.created', ['event_id' => $event->id, 'guest_id' => $guest->id]);

        return [$guest, true];
    }

    public function update(Guest $guest, array $data): Guest
    {
        $guest->fill($data)->save();

        Audit::log('guestbook.updated', ['event_id' => $guest->event_id, 'guest_id' => $guest->id]);

        return $guest;
    }

    public function find(Event $event, string $deviceToken): ?Guest
    {
        return Guest::where('event_id', $event->id)->where('device_token', $deviceToken)->first();
    }
}
