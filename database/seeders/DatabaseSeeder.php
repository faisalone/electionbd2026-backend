<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DivisionSeeder::class,
            DistrictSeeder::class,
            SeatSeeder::class,
            PartySeeder::class,
            SymbolSeeder::class,
            TimelineEventSeeder::class,
            PollSeeder::class,
            NewsSeeder::class, // Added news seeder
        ]);

        $this->command->info('Database seeded successfully!');
    }
}
