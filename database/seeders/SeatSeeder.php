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
        
        // Official seat number mapping (follows Bangladesh parliament constituency order 1-300)
        $seatRanges = [
            'panchagarh' => [1, 2],      // 2 seats
            'thakurgaon' => [3, 5],      // 3 seats
            'nilphamari' => [6, 9],      // 4 seats
            'lalmonirhat' => [10, 12],   // 3 seats
            'rangpur' => [13, 18],       // 6 seats
            'kurigram' => [19, 22],      // 4 seats
            'gaibandha' => [23, 27],     // 5 seats
            'dinajpur' => [28, 33],      // 6 seats
            'joypurhat' => [34, 35],     // 2 seats
            'bogura' => [36, 42],        // 7 seats
            'naogaon' => [43, 48],       // 6 seats
            'natore' => [49, 52],        // 4 seats
            'rajshahi' => [53, 58],      // 6 seats
            'chapainawabganj' => [59, 61], // 3 seats
            'pabna' => [62, 66],         // 5 seats
            'sirajganj' => [67, 72],     // 6 seats
            'tangail' => [73, 80],       // 8 seats
            'jamalpur' => [81, 85],      // 5 seats
            'mymensingh' => [86, 96],    // 11 seats
            'netrokona' => [97, 101],    // 5 seats
            'sherpur' => [102, 104],     // 3 seats
            'kishoreganj' => [105, 110], // 6 seats
            'manikganj' => [111, 113],   // 3 seats
            'dhaka' => [114, 133],       // 20 seats
            'gazipur' => [134, 138],     // 5 seats
            'narsingdi' => [139, 143],   // 5 seats
            'narayanganj' => [144, 148], // 5 seats
            'munshiganj' => [149, 151],  // 3 seats
            'faridpur' => [152, 155],    // 4 seats
            'gopalganj' => [156, 158],   // 3 seats
            'rajbari' => [159, 160],     // 2 seats
            'madaripur' => [161, 163],   // 3 seats
            'shariatpur' => [164, 166],  // 3 seats
            'cumilla' => [167, 177],     // 11 seats
            'brahmanbaria' => [178, 183], // 6 seats
            'chandpur' => [184, 188],    // 5 seats
            'lakshmipur' => [189, 192],  // 4 seats
            'feni' => [193, 195],        // 3 seats
            'noakhali' => [196, 201],    // 6 seats
            'khagrachari' => [202, 202], // 1 seat
            'rangamati' => [203, 203],   // 1 seat
            'bandarban' => [204, 204],   // 1 seat
            'chattogram' => [205, 220],  // 16 seats
            'coxsbazar' => [221, 224],   // 4 seats
            'habiganj' => [225, 228],    // 4 seats
            'moulvibazar' => [229, 232], // 4 seats
            'sunamganj' => [233, 237],   // 5 seats
            'sylhet' => [238, 243],      // 6 seats
            'bagerhat' => [244, 247],    // 4 seats
            'chuadanga' => [248, 249],   // 2 seats
            'jhenaidah' => [250, 253],   // 4 seats
            'jessore' => [254, 259],     // 6 seats
            'magura' => [260, 261],      // 2 seats
            'narail' => [262, 263],      // 2 seats
            'khulna' => [264, 269],      // 6 seats
            'kushtia' => [270, 273],     // 4 seats
            'meherpur' => [274, 275],    // 2 seats
            'satkhira' => [276, 279],    // 4 seats
            'barishal' => [280, 285],    // 6 seats
            'barguna' => [286, 287],     // 2 seats
            'bhola' => [288, 291],       // 4 seats
            'jhalokathi' => [292, 293],  // 2 seats
            'patuakhali' => [294, 297],  // 4 seats
            'pirojpur' => [298, 300],    // 3 seats
        ];
        
        foreach ($seatsData as $districtSlug => $seats) {
            // Find district by name_en (slug)
            $district = District::where('name_en', $districtSlug)->first();
            
            if (!$district) {
                $this->command->warn("District not found: {$districtSlug}");
                continue;
            }

            // Get seat number range for this district
            $range = $seatRanges[$districtSlug] ?? null;
            if (!$range) {
                $this->command->warn("Seat range not found for district: {$districtSlug}");
            }
            
            $seatNumber = $range ? $range[0] : null;

            foreach ($seats as $seatData) {
                Seat::create([
                    'district_id' => $district->id,
                    'name' => $seatData['name'],
                    'name_en' => $seatData['name_en'],
                    'area' => $seatData['area'] ?? null,
                    'seat_number' => $seatNumber,
                ]);
                
                if ($seatNumber) {
                    $seatNumber++;
                }
            }
        }
        
        $this->command->info('Seats seeded successfully with seat numbers!');
    }
}
