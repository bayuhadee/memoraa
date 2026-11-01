<?php

namespace Tests;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /** Simulasikan satu perangkat tamu (cookie device token terenkripsi). */
    protected function asDevice(string $token): static
    {
        // withCredentials: request JSON di test juga membawa cookie (seperti XHR browser).
        return $this->withCredentials()->withCookie(config('galeri.device_cookie.name'), $token);
    }

    protected function newToken(): string
    {
        return Str::random(64);
    }

    /** Buat tamu yang sudah mengisi buku tamu di event tertentu. */
    protected function guestFor(Event $event, ?string $token = null): Guest
    {
        return Guest::factory()->for($event)->create(['device_token' => $token ?? $this->newToken()]);
    }

    protected function jpeg(int $w = 1600, int $h = 1200, string $name = 'foto.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h);
    }
}
