<?php

namespace Database\Seeders;

use App\Models\Owner;
use Illuminate\Database\Seeder;
use RuntimeException;

class OwnerSeeder extends Seeder
{
    /** Satu akun owner dari .env (OWNER_EMAIL, OWNER_PASSWORD). */
    public function run(): void
    {
        $email = config('galeri.owner.email');
        $password = config('galeri.owner.password');

        if (! $email || ! $password) {
            throw new RuntimeException('Isi OWNER_EMAIL dan OWNER_PASSWORD di file .env terlebih dahulu.');
        }

        if (strlen($password) < 10) {
            throw new RuntimeException('OWNER_PASSWORD minimal 10 karakter.');
        }

        Owner::updateOrCreate(
            ['email' => $email],
            ['name' => config('galeri.owner.name'), 'password' => $password],
        );

        $this->command?->info("Akun owner siap: {$email}");
    }
}
