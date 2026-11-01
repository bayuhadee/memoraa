<?php

namespace App\Services;

use App\Models\Event;
use App\Support\Audit;

class EventService
{
    /** Tanpa karakter ambigu: 0/O, 1/I/L. Huruf besar saja agar mudah diketik tamu/host. */
    public const PASSWORD_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct(
        private readonly PhotoUploadService $photos,
        private readonly PhotoStorage $storage,
    ) {}

    public function generatePassword(int $length = 4): string
    {
        $alphabet = self::PASSWORD_ALPHABET;
        $max = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        return $password;
    }

    public function generateCode(): string
    {
        do {
            $code = 'EVT-'.$this->generatePassword(5);
        } while (Event::withTrashed()->where('event_code', $code)->exists());

        return $code;
    }

    public function create(array $data): Event
    {
        $event = new Event($data);
        $event->event_code = ($data['event_code'] ?? null) ?: $this->generateCode();
        $event->setEventPassword($this->generatePassword());
        $event->save();

        return $event;
    }

    public function update(Event $event, array $data): Event
    {
        $event->fill($data)->save();

        return $event;
    }

    public function regeneratePassword(Event $event): string
    {
        $password = $this->generatePassword();
        $event->setEventPassword($password);
        $event->save();

        // Host yang login dengan password lama otomatis keluar (lihat EnsureHostSessionValid).
        Audit::log('event.password_regenerated', ['event_id' => $event->id]);

        return $password;
    }

    /** Hapus semua foto event (record + file). Buku tamu tetap ada. */
    public function resetPhotos(Event $event): int
    {
        $count = $this->photos->deleteMany($event->photos()->cursor(), 'owner-reset');
        $this->storage->deleteEventDirectories($event->id);

        Audit::log('event.photos_reset', ['event_id' => $event->id, 'count' => $count]);

        return $count;
    }

    public function delete(Event $event): void
    {
        $id = $event->id;
        $event->delete(); // foto & file dihapus via model event
        $this->storage->deleteEventDirectories($id);

        Audit::log('event.deleted', ['event_id' => $id]);
    }
}
