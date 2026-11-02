<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * demo@example.com / password with 30 items, the data the React, Next.js and
 * Flutter starters expect locally. Safe to run twice.
 */
final class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public const string EMAIL = 'demo@example.com';

    public const int ITEM_COUNT = 30;

    public function run(): void
    {
        if (User::query()->where('email', self::EMAIL)->exists()) {
            return;
        }

        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => self::EMAIL,
            'password' => 'password',
        ]);

        foreach (range(1, self::ITEM_COUNT) as $number) {
            $createdAt = now()->subMinutes(self::ITEM_COUNT - $number);

            Item::factory()->for($user)->create([
                // Mixed Arabic and English names, so clients see RTL and LTR rows.
                'name' => $number % 2 === 0
                    ? sprintf('عنصر تجريبي %02d', $number)
                    : sprintf('Demo item %02d', $number),
                'description' => $number % 3 === 0 ? null : "Description of item {$number}.",
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
