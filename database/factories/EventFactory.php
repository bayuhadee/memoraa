<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'event_code' => 'EVT-'.strtoupper(Str::random(6)),
            'bride_groom_names' => fake()->firstName().' & '.fake()->firstName(),
            'event_date' => now(config('galeri.timezone'))->addDays(7)->toDateString(),
            'address' => fake()->address(),
            'payment_status' => 'baru',
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Event $event) {
            if (! $event->password_hash) {
                $event->setEventPassword('AB23');
            }
        });
    }

    public function withPassword(string $password): static
    {
        return $this->afterMaking(fn (Event $event) => $event->setEventPassword($password));
    }

    public function past(): static
    {
        return $this->state(fn () => ['event_date' => now(config('galeri.timezone'))->subDay()->toDateString()]);
    }
}
