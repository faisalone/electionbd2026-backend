<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            // Dhaka Division (13 districts)
            ['division' => 'dhaka', 'name' => 'ঢাকা', 'name_en' => 'dhaka'],
            ['division' => 'dhaka', 'name' => 'গাজীপুর', 'name_en' => 'gazipur'],
            ['division' => 'dhaka', 'name' => 'কিশোরগঞ্জ', 'name_en' => 'kishoreganj'],
            ['division' => 'dhaka', 'name' => 'মানিকগঞ্জ', 'name_en' => 'manikganj'],
            ['division' => 'dhaka', 'name' => 'মুন্সিগঞ্জ', 'name_en' => 'munshiganj'],
            ['division' => 'dhaka', 'name' => 'নারায়ণগঞ্জ', 'name_en' => 'narayanganj'],
            ['division' => 'dhaka', 'name' => 'নরসিংদী', 'name_en' => 'narsingdi'],
            ['division' => 'dhaka', 'name' => 'টাঙ্গাইল', 'name_en' => 'tangail'],
            ['division' => 'dhaka', 'name' => 'ফরিদপুর', 'name_en' => 'faridpur'],
            ['division' => 'dhaka', 'name' => 'গোপালগঞ্জ', 'name_en' => 'gopalganj'],
            ['division' => 'dhaka', 'name' => 'মাদারীপুর', 'name_en' => 'madaripur'],
            ['division' => 'dhaka', 'name' => 'রাজবাড়ী', 'name_en' => 'rajbari'],
            ['division' => 'dhaka', 'name' => 'শরীয়তপুর', 'name_en' => 'shariatpur'],
            
            // Chattogram Division (11 districts)
            ['division' => 'chattogram', 'name' => 'চট্টগ্রাম', 'name_en' => 'chattogram'],
            ['division' => 'chattogram', 'name' => 'কক্সবাজার', 'name_en' => 'coxsbazar'],
            ['division' => 'chattogram', 'name' => 'কুমিল্লা', 'name_en' => 'cumilla'],
            ['division' => 'chattogram', 'name' => 'ফেনী', 'name_en' => 'feni'],
            ['division' => 'chattogram', 'name' => 'ব্রাহ্মণবাড়িয়া', 'name_en' => 'brahmanbaria'],
            ['division' => 'chattogram', 'name' => 'রাঙ্গামাটি', 'name_en' => 'rangamati'],
            ['division' => 'chattogram', 'name' => 'বান্দরবান', 'name_en' => 'bandarban'],
            ['division' => 'chattogram', 'name' => 'খাগড়াছড়ি', 'name_en' => 'khagrachari'],
            ['division' => 'chattogram', 'name' => 'চাঁদপুর', 'name_en' => 'chandpur'],
            ['division' => 'chattogram', 'name' => 'লক্ষ্মীপুর', 'name_en' => 'lakshmipur'],
            ['division' => 'chattogram', 'name' => 'নোয়াখালী', 'name_en' => 'noakhali'],

            // Rajshahi Division (8 districts)
            ['division' => 'rajshahi', 'name' => 'রাজশাহী', 'name_en' => 'rajshahi'],
            ['division' => 'rajshahi', 'name' => 'বগুড়া', 'name_en' => 'bogura'],
            ['division' => 'rajshahi', 'name' => 'জয়পুরহাট', 'name_en' => 'joypurhat'],
            ['division' => 'rajshahi', 'name' => 'নওগাঁ', 'name_en' => 'naogaon'],
            ['division' => 'rajshahi', 'name' => 'নাটোর', 'name_en' => 'natore'],
            ['division' => 'rajshahi', 'name' => 'চাঁপাইনবাবগঞ্জ', 'name_en' => 'chapainawabganj'],
            ['division' => 'rajshahi', 'name' => 'পাবনা', 'name_en' => 'pabna'],
            ['division' => 'rajshahi', 'name' => 'সিরাজগঞ্জ', 'name_en' => 'sirajganj'],

            // Khulna Division (10 districts)
            ['division' => 'khulna', 'name' => 'খুলনা', 'name_en' => 'khulna'],
            ['division' => 'khulna', 'name' => 'বাগেরহাট', 'name_en' => 'bagerhat'],
            ['division' => 'khulna', 'name' => 'চুয়াডাঙ্গা', 'name_en' => 'chuadanga'],
            ['division' => 'khulna', 'name' => 'যশোর', 'name_en' => 'jessore'],
            ['division' => 'khulna', 'name' => 'ঝিনাইদহ', 'name_en' => 'jhenaidah'],
            ['division' => 'khulna', 'name' => 'কুষ্টিয়া', 'name_en' => 'kushtia'],
            ['division' => 'khulna', 'name' => 'মাগুরা', 'name_en' => 'magura'],
            ['division' => 'khulna', 'name' => 'মেহেরপুর', 'name_en' => 'meherpur'],
            ['division' => 'khulna', 'name' => 'নড়াইল', 'name_en' => 'narail'],
            ['division' => 'khulna', 'name' => 'সাতক্ষীরা', 'name_en' => 'satkhira'],

            // Barishal Division (6 districts)
            ['division' => 'barishal', 'name' => 'বরিশাল', 'name_en' => 'barishal'],
            ['division' => 'barishal', 'name' => 'বরগুনা', 'name_en' => 'barguna'],
            ['division' => 'barishal', 'name' => 'ভোলা', 'name_en' => 'bhola'],
            ['division' => 'barishal', 'name' => 'ঝালকাঠি', 'name_en' => 'jhalokathi'],
            ['division' => 'barishal', 'name' => 'পটুয়াখালী', 'name_en' => 'patuakhali'],
            ['division' => 'barishal', 'name' => 'পিরোজপুর', 'name_en' => 'pirojpur'],

            // Sylhet Division (4 districts)
            ['division' => 'sylhet', 'name' => 'সিলেট', 'name_en' => 'sylhet'],
            ['division' => 'sylhet', 'name' => 'হবিগঞ্জ', 'name_en' => 'habiganj'],
            ['division' => 'sylhet', 'name' => 'মৌলভীবাজার', 'name_en' => 'moulvibazar'],
            ['division' => 'sylhet', 'name' => 'সুনামগঞ্জ', 'name_en' => 'sunamganj'],

            // Rangpur Division (8 districts)
            ['division' => 'rangpur', 'name' => 'রংপুর', 'name_en' => 'rangpur'],
            ['division' => 'rangpur', 'name' => 'দিনাজপুর', 'name_en' => 'dinajpur'],
            ['division' => 'rangpur', 'name' => 'গাইবান্ধা', 'name_en' => 'gaibandha'],
            ['division' => 'rangpur', 'name' => 'কুড়িগ্রাম', 'name_en' => 'kurigram'],
            ['division' => 'rangpur', 'name' => 'লালমনিরহাট', 'name_en' => 'lalmonirhat'],
            ['division' => 'rangpur', 'name' => 'নীলফামারী', 'name_en' => 'nilphamari'],
            ['division' => 'rangpur', 'name' => 'পঞ্চগড়', 'name_en' => 'panchagarh'],
            ['division' => 'rangpur', 'name' => 'ঠাকুরগাঁও', 'name_en' => 'thakurgaon'],

            // Mymensingh Division (4 districts)
            ['division' => 'mymensingh', 'name' => 'ময়মনসিংহ', 'name_en' => 'mymensingh'],
            ['division' => 'mymensingh', 'name' => 'জামালপুর', 'name_en' => 'jamalpur'],
            ['division' => 'mymensingh', 'name' => 'নেত্রকোনা', 'name_en' => 'netrokona'],
            ['division' => 'mymensingh', 'name' => 'শেরপুর', 'name_en' => 'sherpur'],
        ];

        foreach ($districts as $district) {
            $division = Division::where('name_en', $district['division'])->first();
            
            if ($division) {
                District::create([
                    'division_id' => $division->id,
                    'name' => $district['name'],
                    'name_en' => $district['name_en'],
                    // total_seats removed - calculated dynamically from seats
                ]);
            }
        }
    }
}
