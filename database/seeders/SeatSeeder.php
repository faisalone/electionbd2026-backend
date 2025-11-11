<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        $seatsData = require database_path('data/seats_data.php');
        
        foreach ($seatsData as $districtSlug => $seats) {
            // Find district by name_en (slug)
            $district = District::where('name_en', $districtSlug)->first();
            
            if (!$district) {
                $this->command->warn("District not found: {$districtSlug}");
                continue;
            }

            foreach ($seats as $seatData) {
                Seat::create([
                    'district_id' => $district->id,
                    'name' => $seatData['name'],
                    'name_en' => $seatData['name_en'],
                    'area' => $seatData['area'] ?? null,
                ]);
            }
        }
        
        $this->command->info('Seats seeded successfully!');
    }
}
