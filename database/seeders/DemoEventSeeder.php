<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Services\EventService;
use Illuminate\Database\Seeder;

/** Event contoh untuk pengembangan lokal (tidak dijalankan di production). */
class DemoEventSeeder extends Seeder
{
    public function run(EventService $events): void
    {
        if (Event::where('event_code', 'DEMO-2026')->exists()) {
            return;
        }

        $event = $events->create([
            'event_code' => 'DEMO-2026',
            'bride_groom_names' => 'Andi & Bunga',
            'event_date' => now(config('galeri.timezone'))->addDays(14)->toDateString(),
            'address' => 'Gedung Serbaguna Contoh, Jl. Contoh No. 1, Denpasar',
            'payment_status' => 'lunas',
            'is_active' => true,
        ]);

        $this->command?->info("Event demo: {$event->galleryUrl()}  |  ID: {$event->event_code}  |  Password: {$event->password_plain}");
    }
}
