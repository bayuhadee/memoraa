<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_must_sign_guestbook_before_uploading(): void
    {
        $event = Event::factory()->create();

        $this->asDevice($this->newToken())->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg()]])
            ->assertRedirect(route('guest.guestbook', $event));

        $this->assertSame(0, Photo::count());
    }

    public function test_landscape_is_resized_to_1200x800_and_thumb_created(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $this->guestFor($event, $token);

        $this->asDevice($token)->post(route('guest.photos.store', $event), [
            'photos' => [$this->jpeg(3000, 1500)],
            'captions' => ['Bahagia selalu'],
        ])->assertRedirect(route('guest.my-photos', $event));

        $photo = Photo::sole();
        $this->assertSame(Photo::STATUS_READY, $photo->status);
        $this->assertSame('landscape', $photo->orientation);
        $this->assertSame('Bahagia selalu', $photo->caption);
        $this->assertNull($photo->pending_path);

        [$w, $h] = getimagesize(Storage::disk('local')->path($photo->path));
        $this->assertSame([1200, 800], [$w, $h]);

        [$tw] = getimagesize(Storage::disk('local')->path($photo->thumb_path));
        $this->assertSame(400, $tw);

        // File asli tidak disimpan.
        $this->assertSame([], Storage::disk('local')->files('tmp'));
    }

    public function test_portrait_is_resized_to_800x1200(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $this->guestFor($event, $token);

        $this->asDevice($token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg(1000, 2000)]]);

        $photo = Photo::sole();
        $this->assertSame('portrait', $photo->orientation);
        $this->assertSame([800, 1200], array_slice(getimagesize(Storage::disk('local')->path($photo->path)), 0, 2));
    }

    public function test_exif_metadata_is_stripped(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $this->guestFor($event, $token);

        $file = $this->jpeg(1600, 1200);
        $this->injectFakeExif($file->getRealPath());
        $this->assertStringContainsString('Exif', file_get_contents($file->getRealPath()));

        $this->asDevice($token)->post(route('guest.photos.store', $event), ['photos' => [$file]]);

        $output = Storage::disk('local')->get(Photo::sole()->path);
        $this->assertStringNotContainsString('Exif', $output);
        $this->assertStringNotContainsString('GPS-RAHASIA', $output);
    }

    public function test_maximum_two_photos_per_guest(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $this->guestFor($event, $token);

        // Upload 3 sekaligus ditolak.
        $this->asDevice($token)->post(route('guest.photos.store', $event), [
            'photos' => [$this->jpeg(), $this->jpeg(), $this->jpeg()],
        ])->assertSessionHasErrors('photos');
        $this->assertSame(0, Photo::count());

        // 2 sekaligus diterima.
        $this->asDevice($token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg(), $this->jpeg()]]);
        $this->assertSame(2, Photo::count());

        // Foto ketiga (request terpisah) ditolak oleh pengecekan kuota di service.
        $this->asDevice($token)->postJson(route('guest.photos.store', $event), ['photos' => [$this->jpeg()]])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Kuota foto Anda sudah penuh (maksimal 2 foto). Hapus atau ganti foto yang ada.']);
        $this->assertSame(2, Photo::count());
    }

    public function test_one_plus_two_exceeds_quota(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $this->guestFor($event, $token);

        $this->asDevice($token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg()]]);
        $this->asDevice($token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg(), $this->jpeg()]])
            ->assertSessionHas('error', 'Anda hanya bisa menambah 1 foto lagi (maksimal 2 foto).');

        $this->assertSame(1, Photo::count());
    }

    public function test_guest_can_replace_edit_caption_and_delete_own_photo(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $this->guestFor($event, $token);

        $this->asDevice($token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg(1600, 1200)]]);
        $photo = Photo::sole();
        $oldPath = $photo->path;

        // Ganti: landscape -> portrait, file lama dihapus.
        $this->asDevice($token)->post(route('guest.photos.replace', [$event, $photo]), ['photo' => $this->jpeg(900, 1800)])
            ->assertRedirect(route('guest.my-photos', $event));
        $photo->refresh();
        $this->assertSame('portrait', $photo->orientation);
        $this->assertNotSame($oldPath, $photo->path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($photo->path);

        // Caption.
        $this->asDevice($token)->put(route('guest.photos.update', [$event, $photo]), ['caption' => 'Caption baru']);
        $this->assertSame('Caption baru', $photo->fresh()->caption);

        // Hapus: record & file hilang, kuota kembali.
        $paths = [$photo->path, $photo->thumb_path];
        $this->asDevice($token)->delete(route('guest.photos.destroy', [$event, $photo]));
        $this->assertSame(0, Photo::count());
        foreach ($paths as $p) {
            Storage::disk('local')->assertMissing($p);
        }
    }

    public function test_media_served_with_safe_headers(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $this->guestFor($event, $token);
        $this->asDevice($token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg()]]);
        $photo = Photo::sole();

        $this->get(route('media.show', [$photo, 'thumb']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $download = $this->get(route('media.download', $photo))->assertOk();
        $this->assertStringContainsString('attachment', $download->headers->get('Content-Disposition'));
        $this->assertStringContainsString('_1.jpg', $download->headers->get('Content-Disposition'));

        $this->get('/media/'.$photo->uuid.'/../../.env')->assertNotFound();
    }

    /** Sisipkan segmen APP1 (EXIF) palsu tepat setelah marker SOI JPEG. */
    private function injectFakeExif(string $path): void
    {
        $jpeg = file_get_contents($path);
        $payload = "Exif\x00\x00GPS-RAHASIA-LAT-8.65-LON-115.21";
        $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
        file_put_contents($path, substr($jpeg, 0, 2).$segment.substr($jpeg, 2));
    }
}
