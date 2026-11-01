<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Photo;
use App\Services\HostAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function photoFor(Guest $guest): Photo
    {
        $this->asDevice($guest->device_token)
            ->post(route('guest.photos.store', $guest->event), ['photos' => [$this->jpeg()]]);

        return Photo::where('guest_id', $guest->id)->latest('id')->firstOrFail();
    }

    private function loginHost(Event $event)
    {
        return $this->actingAs($event, 'host')
            ->withSession([HostAuthService::SESSION_SIGNATURE => HostAuthService::signature($event)]);
    }

    public function test_guest_cannot_touch_photo_of_another_event(): void
    {
        [$a, $b] = Event::factory()->count(2)->create();
        $token = $this->newToken();
        $this->guestFor($a, $token);
        $foreign = $this->photoFor($this->guestFor($b));

        // Photo event B diakses lewat URL event A → 404 (scoped binding).
        $this->asDevice($token)->delete(route('guest.photos.destroy', [$a, $foreign]))->assertNotFound();
        $this->asDevice($token)->put(route('guest.photos.update', [$a, $foreign]), ['caption' => 'hack'])->assertNotFound();

        $this->assertDatabaseHas('photos', ['id' => $foreign->id, 'caption' => null]);
    }

    public function test_guest_cannot_touch_other_guests_photo_in_same_event(): void
    {
        $event = Event::factory()->create();
        $token = $this->newToken();
        $this->guestFor($event, $token);
        $other = $this->photoFor($this->guestFor($event));

        $this->asDevice($token)->delete(route('guest.photos.destroy', [$event, $other]))->assertForbidden();
        $this->asDevice($token)->post(route('guest.photos.replace', [$event, $other]), ['photo' => $this->jpeg()])->assertForbidden();
        $this->asDevice($token)->put(route('guest.photos.update', [$event, $other]), ['caption' => 'hack'])->assertForbidden();

        $this->assertNull($other->fresh()->caption);
    }

    public function test_same_device_has_separate_identity_per_event(): void
    {
        [$a, $b] = Event::factory()->count(2)->create();
        $token = $this->newToken();
        $this->guestFor($a, $token);

        // Perangkat yang sama belum terdaftar di event B.
        $this->asDevice($token)->get(route('guest.my-photos', $b))->assertRedirect(route('guest.guestbook', $b));
    }

    public function test_gallery_only_lists_photos_of_its_event(): void
    {
        [$a, $b] = Event::factory()->count(2)->create();
        $mine = $this->photoFor($this->guestFor($a));
        $foreign = $this->photoFor($this->guestFor($b));

        $this->get(route('guest.gallery', $a))
            ->assertSee($mine->uuid)
            ->assertDontSee($foreign->uuid);
    }

    public function test_host_cannot_manage_other_events_data(): void
    {
        [$a, $b] = Event::factory()->count(2)->create();
        $foreignGuest = $this->guestFor($b);
        $foreign = $this->photoFor($foreignGuest);

        $this->loginHost($a)->delete(route('host.photos.destroy', $foreign))->assertForbidden();
        $this->loginHost($a)->delete(route('host.photos.bulk-destroy'), ['ids' => [$foreign->uuid]])->assertRedirect();
        $this->loginHost($a)->delete(route('host.guestbook.destroy', $foreignGuest))->assertForbidden();

        $this->assertDatabaseHas('photos', ['id' => $foreign->id]);
        $this->assertNotNull($foreignGuest->fresh()->message);

        $this->loginHost($a)->get(route('host.photos.index'))->assertOk()->assertDontSee($foreign->uuid);
    }

    public function test_host_can_delete_own_photos_and_messages(): void
    {
        $event = Event::factory()->create();
        $guest = $this->guestFor($event);
        $p1 = $this->photoFor($guest);
        $p2 = $this->photoFor($guest);

        $this->loginHost($event)->delete(route('host.photos.bulk-destroy'), ['ids' => [$p1->uuid, $p2->uuid]])
            ->assertSessionHas('success', '2 foto berhasil dihapus.');
        $this->assertSame(0, Photo::count());

        $this->loginHost($event)->delete(route('host.guestbook.destroy', $guest));
        $this->assertNull($guest->fresh()->message);
    }
}
