<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestbookTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge(['name' => 'Budi', 'address' => 'Denpasar', 'message' => 'Selamat menempuh hidup baru'], $override);
    }

    public function test_first_visit_sets_long_lived_httponly_device_cookie(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('guest.gallery', $event))->assertOk();

        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === 'gt_device');
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertGreaterThan(now()->addDays(300)->timestamp, $cookie->getExpiresTime());
    }

    public function test_guest_can_sign_guestbook_once_per_device(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();

        $this->asDevice($token)->post(route('guest.guestbook.store', $event), $this->payload())
            ->assertRedirect(route('guest.my-photos', $event));

        // Percobaan kedua dari perangkat yang sama tidak membuat entri baru.
        $this->asDevice($token)->post(route('guest.guestbook.store', $event), $this->payload(['name' => 'Budi Lagi']))
            ->assertRedirect(route('guest.my-photos', $event))
            ->assertSessionHas('info');

        $this->assertSame(1, Guest::where('event_id', $event->id)->count());
        $this->assertSame('Budi', Guest::first()->name);
    }

    public function test_different_devices_create_separate_entries(): void
    {
        $event = Event::factory()->create();

        $this->asDevice($this->newToken())->post(route('guest.guestbook.store', $event), $this->payload());
        $this->asDevice($this->newToken())->post(route('guest.guestbook.store', $event), $this->payload(['name' => 'Sari']));

        $this->assertSame(2, $event->guests()->count());
    }

    public function test_guest_can_update_own_entry(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $guest = $this->guestFor($event, $token);

        $this->asDevice($token)->put(route('guest.guestbook.update', $event), $this->payload(['message' => 'Ucapan baru']))
            ->assertRedirect(route('guest.my-photos', $event));

        $this->assertSame('Ucapan baru', $guest->fresh()->message);
    }

    public function test_validation_and_honeypot(): void
    {
        $event = Event::factory()->create();

        $this->asDevice($this->newToken())->post(route('guest.guestbook.store', $event), ['name' => '', 'address' => '', 'message' => ''])
            ->assertSessionHasErrors(['name', 'address', 'message']);

        $this->asDevice($this->newToken())->post(route('guest.guestbook.store', $event), $this->payload(['website' => 'http://spam']))
            ->assertSessionHasErrors('website');

        $this->asDevice($this->newToken())->post(route('guest.guestbook.store', $event), $this->payload(['message' => str_repeat('a', 1001)]))
            ->assertSessionHasErrors('message');

        $this->assertSame(0, Guest::count());
    }

    public function test_output_is_escaped(): void
    {
        $event = Event::factory()->create();
        Guest::factory()->for($event)->create(['name' => '<script>alert(1)</script>', 'message' => '<img src=x onerror=alert(1)>']);

        $this->get(route('guest.gallery', $event))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_inactive_or_unknown_event_returns_404(): void
    {
        $event = Event::factory()->create(['is_active' => false]);

        $this->get(route('guest.gallery', $event))->assertNotFound();
        $this->get('/e/TIDAK-ADA')->assertNotFound();
    }

    public function test_security_headers_present(): void
    {
        $event = Event::factory()->create();

        $this->get(route('guest.gallery', $event))
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }
}
