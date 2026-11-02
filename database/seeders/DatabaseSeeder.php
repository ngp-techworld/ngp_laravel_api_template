<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed what local development needs. Never runs outside local/testing:
     * real users come from `php artisan app:create-user` (ngp-laravel §6).
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command->warn('Skipped: demo data is for local development only. Use php artisan app:create-user.');

            return;
        }

        $this->call(DemoSeeder::class);
    }
}
