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
            CandidateSeeder::class, // Added candidate seeder
            TimelineEventSeeder::class,
            PollSeeder::class,
            // NewsSeeder::class, // Removed - news will be generated via artisan command
        ]);

        $this->command->info('Database seeded successfully!');
    }
}
