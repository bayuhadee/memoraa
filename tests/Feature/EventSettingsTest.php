<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): Owner
    {
        return Owner::create(['name' => 'Owner', 'email' => 'owner@test.id', 'password' => 'rahasia-panjang-123']);
    }

    private function eventPayload(array $override = []): array
    {
        return array_merge([
            'event_code' => 'NIKAH-1', 'bride_groom_names' => 'A & B', 'event_date' => now()->addDays(3)->toDateString(),
            'address' => 'Bali', 'payment_status' => 'baru', 'is_active' => 1,
        ], $override);
    }

    private function guestPayload(): array
    {
        return ['name' => 'Budi', 'address' => 'Denpasar', 'message' => 'Selamat'];
    }

    public function test_owner_can_set_and_clear_max_guests(): void
    {
        $this->actingAs($this->owner(), 'owner');

        $this->post(route('owner.events.store'), $this->eventPayload(['max_guests' => 0]))->assertSessionHasErrors('max_guests');

        $this->post(route('owner.events.store'), $this->eventPayload(['max_guests' => 50]))->assertRedirect();
        $event = Event::sole();
        $this->assertSame(50, $event->max_guests);

        $this->put(route('owner.events.update', $event), $this->eventPayload(['max_guests' => '']))->assertRedirect();
        $this->assertNull($event->fresh()->max_guests);
    }

    public function test_guestbook_rejects_new_guests_when_full_but_existing_guest_can_edit(): void
    {
        $event = Event::factory()->create(['max_guests' => 1]);
        $first = $this->newToken();

        $this->asDevice($first)->post(route('guest.guestbook.store', $event), $this->guestPayload())
            ->assertRedirect(route('guest.my-photos', $event));

        // Perangkat lain ditolak.
        $other = $this->newToken();
        $this->asDevice($other)->post(route('guest.guestbook.store', $event), $this->guestPayload())
            ->assertRedirect(route('guest.gallery', $event))
            ->assertSessionHas('error');
        $this->asDevice($other)->get(route('guest.guestbook', $event))->assertRedirect(route('guest.gallery', $event));
        $this->asDevice($other)->get(route('guest.gallery', $event))->assertSee('Mohon maaf buku tamu telah penuh');
        $this->assertSame(1, Guest::count());

        // Tamu yang sudah terdaftar tetap bisa membuka & mengubah buku tamu.
        $this->asDevice($first)->get(route('guest.guestbook', $event))->assertOk();
        $this->asDevice($first)->put(route('guest.guestbook.update', $event), ['message' => 'Baru'] + $this->guestPayload())
            ->assertRedirect(route('guest.my-photos', $event));
        $this->assertSame('Baru', Guest::first()->message);
    }

    public function test_owner_can_upload_replace_and_remove_cover(): void
    {
        $this->actingAs($this->owner(), 'owner');

        $this->post(route('owner.events.store'), $this->eventPayload(['cover' => $this->jpeg(3000, 2000)]))->assertRedirect();
        $event = Event::sole();
        $first = $event->cover_path;
        $this->assertNotNull($first);
        Storage::disk('local')->assertExists($first);

        // Diperkecil ke batas config.
        [$w] = getimagesizefromstring(Storage::disk('local')->get($first));
        $this->assertSame(config('galeri.cover.max_dimension'), $w);

        // Tampil di halaman tamu & bisa diambil.
        $this->get(route('guest.gallery', $event))->assertSee(route('media.cover', $event), false);
        $this->get($event->coverUrl())->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        // Ganti: file lama dihapus.
        $this->put(route('owner.events.update', $event), $this->eventPayload(['cover' => $this->jpeg()]))->assertRedirect();
        $second = $event->fresh()->cover_path;
        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);

        // Hapus.
        $this->put(route('owner.events.update', $event), $this->eventPayload(['remove_cover' => 1]))->assertRedirect();
        $this->assertNull($event->fresh()->cover_path);
        Storage::disk('local')->assertMissing($second);
        $this->get(route('media.cover', $event))->assertNotFound();
    }

    public function test_cover_rejects_non_image_and_hides_for_inactive_event(): void
    {
        $this->actingAs($this->owner(), 'owner');

        $this->post(route('owner.events.store'), $this->eventPayload([
            'cover' => UploadedFile::fake()->createWithContent('x.jpg', '<?php echo 1;'),
        ]))->assertSessionHasErrors('cover');
        $this->assertSame(0, Event::count());

        $this->post(route('owner.events.store'), $this->eventPayload(['cover' => $this->jpeg(), 'is_active' => 0]));
        $event = Event::sole();

        $this->get(route('media.cover', $event))->assertOk(); // owner masih bisa melihat

        auth('owner')->logout();
        $this->get(route('media.cover', $event))->assertNotFound();
    }

    public function test_reset_photos_keeps_cover_and_delete_event_removes_it(): void
    {
        $this->actingAs($this->owner(), 'owner');
        $this->post(route('owner.events.store'), $this->eventPayload(['cover' => $this->jpeg()]));
        $event = Event::sole();
        $path = $event->cover_path;

        $this->post(route('owner.events.reset-photos', $event), ['confirm_code' => 'NIKAH-1']);
        Storage::disk('local')->assertExists($path);

        $this->delete(route('owner.events.destroy', $event));
        Storage::disk('local')->assertMissing($path);
    }
}
