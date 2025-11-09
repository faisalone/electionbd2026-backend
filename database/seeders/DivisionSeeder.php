<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['name' => 'ঢাকা', 'name_en' => 'dhaka'],
            ['name' => 'চট্টগ্রাম', 'name_en' => 'chattogram'],
            ['name' => 'রাজশাহী', 'name_en' => 'rajshahi'],
            ['name' => 'খুলনা', 'name_en' => 'khulna'],
            ['name' => 'বরিশাল', 'name_en' => 'barishal'],
            ['name' => 'সিলেট', 'name_en' => 'sylhet'],
            ['name' => 'রংপুর', 'name_en' => 'rangpur'],
            ['name' => 'ময়মনসিংহ', 'name_en' => 'mymensingh'],
        ];

        foreach ($divisions as $division) {
            Division::create($division);
        }
    }
}
