<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OwnerSeeder::class);

        if (app()->environment('local')) {
            $this->call(DemoEventSeeder::class);
        }
    }
}
