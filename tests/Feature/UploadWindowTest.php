<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UploadWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_upload_open_until_2359_on_event_day_in_configured_timezone(): void
    {
        $event = Event::factory()->create(['event_date' => '2026-11-14']);

        Carbon::setTestNow(Carbon::parse('2026-11-14 23:59:59', 'Asia/Makassar'));
        $this->assertTrue($event->fresh()->isUploadOpen());

        Carbon::setTestNow(Carbon::parse('2026-11-15 00:00:00', 'Asia/Makassar'));
        $this->assertFalse($event->fresh()->isUploadOpen());

        // 23:30 WIB = 00:30 WITA keesokan harinya → sudah tutup.
        Carbon::setTestNow(Carbon::parse('2026-11-14 23:30:00', 'Asia/Jakarta'));
        $this->assertFalse($event->fresh()->isUploadOpen());
    }

    public function test_all_write_actions_blocked_after_event_date(): void
    {
        $event = Event::factory()->past()->create();
        $token = $this->newToken();
        $guest = $this->guestFor($event, $token);
        $photo = $this->readyPhoto($guest);

        $payload = ['name' => 'X', 'address' => 'Y', 'message' => 'Z'];

        $this->asDevice($this->newToken())->post(route('guest.guestbook.store', $event), $payload)
            ->assertRedirect(route('guest.gallery', $event))->assertSessionHas('error');
        $this->asDevice($token)->put(route('guest.guestbook.update', $event), $payload)->assertRedirect(route('guest.gallery', $event));
        $this->asDevice($token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg()]])->assertRedirect(route('guest.gallery', $event));
        $this->asDevice($token)->put(route('guest.photos.update', [$event, $photo]), ['caption' => 'baru'])->assertRedirect(route('guest.gallery', $event));
        $this->asDevice($token)->delete(route('guest.photos.destroy', [$event, $photo]))->assertRedirect(route('guest.gallery', $event));
        $this->asDevice($token)->postJson(route('guest.photos.replace', [$event, $photo]), ['photo' => $this->jpeg()])->assertForbidden();

        $this->assertSame(1, Guest::count());
        $this->assertSame(1, Photo::count());
        $this->assertNotSame('baru', $photo->fresh()->caption);
        $this->assertNotSame('X', $guest->fresh()->name);
    }

    public function test_gallery_still_viewable_and_downloadable_after_event(): void
    {
        $event = Event::factory()->past()->create();
        $photo = $this->readyPhoto($this->guestFor($event));

        $this->get(route('guest.gallery', $event))
            ->assertOk()
            ->assertSee('Periode upload telah berakhir')
            ->assertDontSee('Isi Buku Tamu &amp; Upload Foto', false);

        $this->get(route('media.download', $photo))->assertOk();
    }

    private function readyPhoto(Guest $guest): Photo
    {
        // Proses lewat jalur normal saat upload masih dibuka.
        $event = $guest->event;
        $original = $event->event_date;
        $event->update(['event_date' => now()->addDay()->toDateString()]);

        $this->asDevice($guest->device_token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg()]]);

        $event->update(['event_date' => $original]);

        return Photo::latest('id')->first();
    }
}
