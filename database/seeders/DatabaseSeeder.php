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
            AdminSeeder::class,
            DivisionSeeder::class,
            DistrictSeeder::class,
            SeatSeeder::class,
            SymbolSeeder::class,     // Must run before PartySeeder (parties reference symbols)
            PartySeeder::class,
            CandidateSeeder::class,  // Must run after PartySeeder and SeatSeeder
            TimelineEventSeeder::class,
            PollSeeder::class,
            // NewsSeeder::class, // Removed - news will be generated via artisan command
        ]);

        $this->command->info('Database seeded successfully!');
    }
}
