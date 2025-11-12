<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        // Official order based on seat numbering (1-8)
        $divisions = [
            ['name' => 'রংপুর', 'name_en' => 'rangpur', 'order' => 1],      // Seats 1-33
            ['name' => 'রাজশাহী', 'name_en' => 'rajshahi', 'order' => 2],   // Seats 34-72
            ['name' => 'খুলনা', 'name_en' => 'khulna', 'order' => 3],        // Seats 73-108
            ['name' => 'বরিশাল', 'name_en' => 'barishal', 'order' => 4],    // Seats 109-129
            ['name' => 'ঢাকা', 'name_en' => 'dhaka', 'order' => 5],          // Seats 130-223
            ['name' => 'ময়মনসিংহ', 'name_en' => 'mymensingh', 'order' => 6], // Seats 146-161
            ['name' => 'সিলেট', 'name_en' => 'sylhet', 'order' => 7],       // Seats 224-242
            ['name' => 'চট্টগ্রাম', 'name_en' => 'chattogram', 'order' => 8], // Seats 243-300
        ];

        foreach ($divisions as $division) {
            Division::create($division);
        }
    }
}
