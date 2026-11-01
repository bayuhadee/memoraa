<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use ZipArchive;

class HostAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_logs_in_with_event_code_and_password(): void
    {
        $event = Event::factory()->withPassword('K7PQ')->create(['event_code' => 'NIKAH-AB12']);

        // Huruf kecil tetap diterima (dinormalisasi).
        $this->post(route('host.login.store'), ['event_code' => 'nikah-ab12', 'password' => 'k7pq'])
            ->assertRedirect(route('host.dashboard'));

        $this->assertAuthenticatedAs($event, 'host');
        $this->get(route('host.dashboard'))->assertOk()->assertSee($event->bride_groom_names);
    }

    public function test_wrong_password_is_rejected(): void
    {
        Event::factory()->withPassword('K7PQ')->create(['event_code' => 'NIKAH-AB12']);

        $this->post(route('host.login.store'), ['event_code' => 'NIKAH-AB12', 'password' => 'XXXX'])
            ->assertSessionHasErrors(['event_code' => 'ID event atau password salah.']);

        $this->assertGuest('host');
    }

    public function test_login_is_rate_limited_after_5_failures(): void
    {
        Event::factory()->withPassword('K7PQ')->create(['event_code' => 'NIKAH-AB12']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('host.login.store'), ['event_code' => 'NIKAH-AB12', 'password' => 'XXXX']);
        }

        // Bahkan password benar ditolak selama terkunci.
        $this->post(route('host.login.store'), ['event_code' => 'NIKAH-AB12', 'password' => 'K7PQ'])
            ->assertSessionHasErrors('event_code');
        $this->assertGuest('host');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('event_code'));
    }

    public function test_inactive_or_deleted_event_cannot_login(): void
    {
        Event::factory()->withPassword('K7PQ')->create(['event_code' => 'OFF-1', 'is_active' => false]);
        $deleted = Event::factory()->withPassword('K7PQ')->create(['event_code' => 'DEL-1']);
        $deleted->delete();

        $this->post(route('host.login.store'), ['event_code' => 'OFF-1', 'password' => 'K7PQ'])->assertSessionHasErrors();
        RateLimiter::clear('host-login:ip:127.0.0.1');
        $this->post(route('host.login.store'), ['event_code' => 'DEL-1', 'password' => 'K7PQ'])->assertSessionHasErrors();

        $this->assertGuest('host');
    }

    public function test_regenerating_password_logs_host_out(): void
    {
        $event = Event::factory()->withPassword('K7PQ')->create(['event_code' => 'NIKAH-AB12']);
        $this->post(route('host.login.store'), ['event_code' => 'NIKAH-AB12', 'password' => 'K7PQ']);
        $this->get(route('host.dashboard'))->assertOk();

        app(EventService::class)->regeneratePassword($event);
        $this->app['auth']->forgetGuards(); // request berikutnya memuat ulang user dari DB

        $this->get(route('host.dashboard'))->assertRedirect(route('host.login'));
    }

    public function test_dashboard_requires_login(): void
    {
        $this->get(route('host.dashboard'))->assertRedirect(route('host.login'));
    }

    public function test_host_downloads_zip_with_named_files(): void
    {
        $event = Event::factory()->withPassword('K7PQ')->create(['event_code' => 'ZIP-1']);
        $guest = $this->guestFor($event);
        $guest->update(['name' => 'Budi Santoso']);
        $this->asDevice($guest->device_token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg(), $this->jpeg()]]);

        $this->post(route('host.login.store'), ['event_code' => 'ZIP-1', 'password' => 'K7PQ']);
        $response = $this->post(route('host.zip.store'))->assertOk();

        $zip = new ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        sort($names);
        $this->assertSame(['budi-santoso_1.jpg', 'budi-santoso_2.jpg'], $names);
    }

    public function test_host_qrcode_is_png(): void
    {
        $event = Event::factory()->withPassword('K7PQ')->create(['event_code' => 'QR-1']);
        $this->post(route('host.login.store'), ['event_code' => 'QR-1', 'password' => 'K7PQ']);

        $png = $this->get(route('host.qrcode'))->assertOk()->assertHeader('Content-Type', 'image/png')->getContent();
        $this->assertStringStartsWith("\x89PNG", $png);
    }
}
