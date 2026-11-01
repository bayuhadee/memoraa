<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Owner;
use App\Models\Photo;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerTest extends TestCase
{
    use RefreshDatabase;

    private Owner $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = Owner::create(['name' => 'Owner', 'email' => 'owner@test.id', 'password' => 'rahasia-panjang-123']);
    }

    public function test_owner_login_and_guard_separation(): void
    {
        $this->post(route('owner.login.store'), ['email' => 'owner@test.id', 'password' => 'salah'])->assertSessionHasErrors('email');

        $this->post(route('owner.login.store'), ['email' => 'owner@test.id', 'password' => 'rahasia-panjang-123'])
            ->assertRedirect(route('owner.dashboard'));
        $this->get(route('owner.dashboard'))->assertOk();

        // Login owner tidak memberi akses ke panel host.
        $this->get(route('host.dashboard'))->assertRedirect(route('host.login'));
    }

    public function test_owner_area_requires_login(): void
    {
        $this->get(route('owner.events.index'))->assertRedirect(route('owner.login'));
    }

    public function test_password_generator_uses_unambiguous_characters(): void
    {
        $service = app(EventService::class);
        for ($i = 0; $i < 300; $i++) {
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{4}$/', $service->generatePassword());
        }
    }

    public function test_create_event_generates_password_and_validates_code(): void
    {
        $this->actingAs($this->owner, 'owner');

        $this->post(route('owner.events.store'), [
            'event_code' => 'nikah ab!', 'bride_groom_names' => 'A & B', 'event_date' => '2026-12-01',
            'address' => 'Bali', 'payment_status' => 'baru', 'is_active' => 1,
        ])->assertSessionHasErrors('event_code');

        $this->post(route('owner.events.store'), [
            'event_code' => 'nikah-ab12', 'bride_groom_names' => 'A & B', 'event_date' => '2026-12-01',
            'address' => 'Bali', 'payment_status' => 'baru', 'is_active' => 1,
        ])->assertRedirect();

        $event = Event::sole();
        $this->assertSame('NIKAH-AB12', $event->event_code);
        $this->assertSame(4, strlen($event->password_plain));
        // Password tersimpan terenkripsi di database, bukan teks biasa.
        $this->assertNotSame($event->password_plain, $event->getRawOriginal('password_plain'));
        $this->assertTrue(password_verify($event->password_plain, $event->password_hash));

        // ID event otomatis jika dikosongkan.
        $this->post(route('owner.events.store'), [
            'bride_groom_names' => 'C & D', 'event_date' => '2026-12-02', 'address' => 'Bali', 'payment_status' => 'lunas',
        ]);
        $this->assertMatchesRegularExpression('/^EVT-[A-Z0-9]{5}$/', Event::latest('id')->first()->event_code);
    }

    public function test_update_payment_status_inline(): void
    {
        $this->actingAs($this->owner, 'owner');
        $event = Event::factory()->create();

        $this->patch(route('owner.events.payment-status', $event), ['payment_status' => 'lunas'])->assertRedirect();
        $this->assertSame('lunas', $event->fresh()->payment_status->value);

        $this->patch(route('owner.events.payment-status', $event), ['payment_status' => 'gratis'])->assertSessionHasErrors();
    }

    public function test_reset_photos_requires_typed_event_code_and_deletes_files(): void
    {
        $event = Event::factory()->create(['event_code' => 'RESET-1']);
        $guest = $this->guestFor($event);
        $this->asDevice($guest->device_token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg(), $this->jpeg()]]);
        $this->assertSame(2, Photo::count());

        $this->actingAs($this->owner, 'owner');

        $this->post(route('owner.events.reset-photos', $event), ['confirm_code' => 'SALAH'])->assertSessionHasErrors('confirm_code');
        $this->assertSame(2, Photo::count());

        $this->post(route('owner.events.reset-photos', $event), ['confirm_code' => 'reset-1'])->assertSessionHas('success');
        $this->assertSame(0, Photo::count());
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
        $this->assertSame(1, $event->guests()->count());
    }

    public function test_deleting_event_removes_photo_files_and_frees_code(): void
    {
        $event = Event::factory()->create(['event_code' => 'HAPUS-1']);
        $guest = $this->guestFor($event);
        $this->asDevice($guest->device_token)->post(route('guest.photos.store', $event), ['photos' => [$this->jpeg()]]);

        $this->actingAs($this->owner, 'owner')->delete(route('owner.events.destroy', $event))->assertRedirect(route('owner.events.index'));

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertSame(0, Photo::count());
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
        $this->get('/e/HAPUS-1')->assertNotFound();

        // ID event bisa dipakai lagi.
        $this->assertFalse(Event::withTrashed()->where('event_code', 'HAPUS-1')->exists());
    }

    public function test_report_filters_and_csv_export(): void
    {
        $this->actingAs($this->owner, 'owner');
        Event::factory()->create(['event_code' => 'R-NOV', 'event_date' => '2026-11-10', 'payment_status' => 'lunas']);
        Event::factory()->create(['event_code' => 'R-DES', 'event_date' => '2026-12-10', 'payment_status' => 'belum_bayar']);
        Event::factory()->create(['event_code' => 'R-NOV2', 'event_date' => '2026-11-20', 'payment_status' => 'baru', 'bride_groom_names' => '=HYPERLINK("x")']);

        $this->get(route('owner.reports.index', ['from' => '2026-11-01', 'to' => '2026-11-30']))
            ->assertOk()->assertSee('R-NOV')->assertSee('R-NOV2')->assertDontSee('R-DES');

        $this->get(route('owner.reports.index', ['status' => 'lunas']))
            ->assertSee('R-NOV')->assertDontSee('R-DES')->assertDontSee('R-NOV2');

        $csv = $this->get(route('owner.reports.export', ['from' => '2026-11-01', 'to' => '2026-11-30']))
            ->assertOk()->streamedContent();
        $this->assertStringContainsString('R-NOV;', $csv);
        $this->assertStringNotContainsString('R-DES', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv); // dicegah formula injection

        $this->get(route('owner.reports.print'))->assertOk();
    }

    public function test_owner_pages_render(): void
    {
        $this->actingAs($this->owner, 'owner');
        $event = Event::factory()->create();

        $this->get(route('owner.dashboard'))->assertOk();
        $this->get(route('owner.events.index'))->assertOk()->assertSee($event->event_code);
        $this->get(route('owner.events.create'))->assertOk();
        $this->get(route('owner.events.show', $event))->assertOk()->assertSee($event->password_plain);
        $this->get(route('owner.events.edit', $event))->assertOk();
        $this->get(route('owner.events.qrcode', $event))->assertOk()->assertHeader('Content-Type', 'image/png');
    }
}
