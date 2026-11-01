<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_has_whatsapp_cta(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('https://wa.me/6281995034478?text=Halo%20Admin%2C%20saya%20ingin%20mendaftar%20event%20galeri%20foto.', false)
            ->assertSee('Daftar Sekarang')
            ->assertSee('Cara Kerja');
    }

    public function test_guest_and_host_pages_render_with_photos(): void
    {
        $event = Event::factory()->withPassword('K7PQ')->create(['event_code' => 'RENDER-1']);
        $token = $this->newToken();
        $guest = $this->guestFor($event, $token);

        $this->asDevice($token)->post(route('guest.photos.store', $event), [
            'photos' => [$this->jpeg(), $this->jpeg(900, 1600)],
            'captions' => ['Satu', 'Dua'],
        ]);
        $this->assertSame(2, Photo::count());

        $this->asDevice($token)->get(route('guest.gallery', $event))->assertOk()->assertSee('Foto Saya')->assertSee('Satu');
        $this->asDevice($token)->get(route('guest.guestbook', $event))->assertOk()->assertSee('Ubah Buku Tamu');
        $this->asDevice($token)->get(route('guest.my-photos', $event))->assertOk()->assertSee('Sisa kuota: <strong>0 foto</strong>', false);
        $this->asDevice($token)->getJson(route('guest.my-photos.status', $event))->assertJson(['processing' => 0]);

        // Infinite scroll mengembalikan potongan HTML.
        $this->asDevice($token)->getJson(route('guest.gallery', [$event, 'page' => 1]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonStructure(['html', 'next']);

        $this->asDevice($this->newToken())->get(route('guest.guestbook', $event))->assertOk()->assertSee('Isi Buku Tamu');

        $this->post(route('host.login.store'), ['event_code' => 'RENDER-1', 'password' => 'K7PQ']);
        $this->get(route('host.dashboard'))->assertOk();
        $this->get(route('host.photos.index'))->assertOk()->assertSee($guest->name);
        $this->get(route('host.guestbook.index'))->assertOk()->assertSee($guest->message);
        $this->get(route('host.zip.index'))->assertOk();
        $this->get(route('host.login'))->assertRedirect(route('host.dashboard'));
    }
}
