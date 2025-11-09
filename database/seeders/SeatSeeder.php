<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        // Generate seats for each district based on their total_seats count
        $districts = District::all();

        foreach ($districts as $district) {
            for ($i = 1; $i <= $district->total_seats; $i++) {
                Seat::create([
                    'district_id' => $district->id,
                    'name' => $district->name . '-' . $this->toBengaliNumber($i),
                    'name_en' => $district->name_en . '-' . $i,
                    'area' => $district->name . ' এলাকা ' . $this->toBengaliNumber($i),
                ]);
            }
        }
    }

    private function toBengaliNumber($number)
    {
        $bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($englishDigits, $bengaliDigits, (string)$number);
    }
}
