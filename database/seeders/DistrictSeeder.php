<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        // Districts ordered by official seat numbering (order 1-64)
        $districts = [
            // Rangpur Division (seats 1-33)
            ['division' => 'rangpur', 'name' => 'পঞ্চগড়', 'name_en' => 'panchagarh', 'order' => 1],
            ['division' => 'rangpur', 'name' => 'ঠাকুরগাঁও', 'name_en' => 'thakurgaon', 'order' => 2],
            ['division' => 'rangpur', 'name' => 'দিনাজপুর', 'name_en' => 'dinajpur', 'order' => 3],
            ['division' => 'rangpur', 'name' => 'নীলফামারী', 'name_en' => 'nilphamari', 'order' => 4],
            ['division' => 'rangpur', 'name' => 'লালমনিরহাট', 'name_en' => 'lalmonirhat', 'order' => 5],
            ['division' => 'rangpur', 'name' => 'রংপুর', 'name_en' => 'rangpur', 'order' => 6],
            ['division' => 'rangpur', 'name' => 'কুড়িগ্রাম', 'name_en' => 'kurigram', 'order' => 7],
            ['division' => 'rangpur', 'name' => 'গাইবান্ধা', 'name_en' => 'gaibandha', 'order' => 8],
            
            // Rajshahi Division (seats 34-72)
            ['division' => 'rajshahi', 'name' => 'জয়পুরহাট', 'name_en' => 'joypurhat', 'order' => 9],
            ['division' => 'rajshahi', 'name' => 'বগুড়া', 'name_en' => 'bogura', 'order' => 10],
            ['division' => 'rajshahi', 'name' => 'চাঁপাইনবাবগঞ্জ', 'name_en' => 'chapainawabganj', 'order' => 11],
            ['division' => 'rajshahi', 'name' => 'নওগাঁ', 'name_en' => 'naogaon', 'order' => 12],
            ['division' => 'rajshahi', 'name' => 'রাজশাহী', 'name_en' => 'rajshahi', 'order' => 13],
            ['division' => 'rajshahi', 'name' => 'নাটোর', 'name_en' => 'natore', 'order' => 14],
            ['division' => 'rajshahi', 'name' => 'সিরাজগঞ্জ', 'name_en' => 'sirajganj', 'order' => 15],
            ['division' => 'rajshahi', 'name' => 'পাবনা', 'name_en' => 'pabna', 'order' => 16],
            
            // Khulna Division (seats 73-108)
            ['division' => 'khulna', 'name' => 'মেহেরপুর', 'name_en' => 'meherpur', 'order' => 17],
            ['division' => 'khulna', 'name' => 'কুষ্টিয়া', 'name_en' => 'kushtia', 'order' => 18],
            ['division' => 'khulna', 'name' => 'চুয়াডাঙ্গা', 'name_en' => 'chuadanga', 'order' => 19],
            ['division' => 'khulna', 'name' => 'ঝিনাইদহ', 'name_en' => 'jhenaidah', 'order' => 20],
            ['division' => 'khulna', 'name' => 'যশোর', 'name_en' => 'jessore', 'order' => 21],
            ['division' => 'khulna', 'name' => 'মাগুরা', 'name_en' => 'magura', 'order' => 22],
            ['division' => 'khulna', 'name' => 'নড়াইল', 'name_en' => 'narail', 'order' => 23],
            ['division' => 'khulna', 'name' => 'বাগেরহাট', 'name_en' => 'bagerhat', 'order' => 24],
            ['division' => 'khulna', 'name' => 'খুলনা', 'name_en' => 'khulna', 'order' => 25],
            ['division' => 'khulna', 'name' => 'সাতক্ষীরা', 'name_en' => 'satkhira', 'order' => 26],
            
            // Barishal Division (seats 109-129)
            ['division' => 'barishal', 'name' => 'বরগুনা', 'name_en' => 'barguna', 'order' => 27],
            ['division' => 'barishal', 'name' => 'পটুয়াখালী', 'name_en' => 'patuakhali', 'order' => 28],
            ['division' => 'barishal', 'name' => 'ভোলা', 'name_en' => 'bhola', 'order' => 29],
            ['division' => 'barishal', 'name' => 'বরিশাল', 'name_en' => 'barishal', 'order' => 30],
            ['division' => 'barishal', 'name' => 'ঝালকাঠি', 'name_en' => 'jhalokathi', 'order' => 31],
            ['division' => 'barishal', 'name' => 'পিরোজপুর', 'name_en' => 'pirojpur', 'order' => 32],
            
            // Dhaka Division (seats 130-223)
            ['division' => 'dhaka', 'name' => 'টাঙ্গাইল', 'name_en' => 'tangail', 'order' => 33],
            ['division' => 'dhaka', 'name' => 'জামালপুর', 'name_en' => 'jamalpur', 'order' => 34],
            ['division' => 'dhaka', 'name' => 'শেরপুর', 'name_en' => 'sherpur', 'order' => 35],
            
            // Mymensingh Division (seats 146-161)
            ['division' => 'mymensingh', 'name' => 'ময়মনসিংহ', 'name_en' => 'mymensingh', 'order' => 36],
            ['division' => 'mymensingh', 'name' => 'নেত্রকোনা', 'name_en' => 'netrokona', 'order' => 37],
            
            // Dhaka Division continued (seats 162-223)
            ['division' => 'dhaka', 'name' => 'কিশোরগঞ্জ', 'name_en' => 'kishoreganj', 'order' => 38],
            ['division' => 'dhaka', 'name' => 'মানিকগঞ্জ', 'name_en' => 'manikganj', 'order' => 39],
            ['division' => 'dhaka', 'name' => 'মুন্সিগঞ্জ', 'name_en' => 'munshiganj', 'order' => 40],
            ['division' => 'dhaka', 'name' => 'ঢাকা', 'name_en' => 'dhaka', 'order' => 41],
            ['division' => 'dhaka', 'name' => 'গাজীপুর', 'name_en' => 'gazipur', 'order' => 42],
            ['division' => 'dhaka', 'name' => 'নরসিংদী', 'name_en' => 'narsingdi', 'order' => 43],
            ['division' => 'dhaka', 'name' => 'নারায়ণগঞ্জ', 'name_en' => 'narayanganj', 'order' => 44],
            ['division' => 'dhaka', 'name' => 'রাজবাড়ী', 'name_en' => 'rajbari', 'order' => 45],
            ['division' => 'dhaka', 'name' => 'ফরিদপুর', 'name_en' => 'faridpur', 'order' => 46],
            ['division' => 'dhaka', 'name' => 'গোপালগঞ্জ', 'name_en' => 'gopalganj', 'order' => 47],
            ['division' => 'dhaka', 'name' => 'মাদারীপুর', 'name_en' => 'madaripur', 'order' => 48],
            ['division' => 'dhaka', 'name' => 'শরীয়তপুর', 'name_en' => 'shariatpur', 'order' => 49],
            
            // Sylhet Division (seats 224-242)
            ['division' => 'sylhet', 'name' => 'সুনামগঞ্জ', 'name_en' => 'sunamganj', 'order' => 50],
            ['division' => 'sylhet', 'name' => 'সিলেট', 'name_en' => 'sylhet', 'order' => 51],
            ['division' => 'sylhet', 'name' => 'মৌলভীবাজার', 'name_en' => 'moulvibazar', 'order' => 52],
            ['division' => 'sylhet', 'name' => 'হবিগঞ্জ', 'name_en' => 'habiganj', 'order' => 53],
            
            // Chattogram Division (seats 243-300)
            ['division' => 'chattogram', 'name' => 'ব্রাহ্মণবাড়িয়া', 'name_en' => 'brahmanbaria', 'order' => 54],
            ['division' => 'chattogram', 'name' => 'কুমিল্লা', 'name_en' => 'cumilla', 'order' => 55],
            ['division' => 'chattogram', 'name' => 'চাঁদপুর', 'name_en' => 'chandpur', 'order' => 56],
            ['division' => 'chattogram', 'name' => 'ফেনী', 'name_en' => 'feni', 'order' => 57],
            ['division' => 'chattogram', 'name' => 'নোয়াখালী', 'name_en' => 'noakhali', 'order' => 58],
            ['division' => 'chattogram', 'name' => 'লক্ষ্মীপুর', 'name_en' => 'lakshmipur', 'order' => 59],
            ['division' => 'chattogram', 'name' => 'চট্টগ্রাম', 'name_en' => 'chattogram', 'order' => 60],
            ['division' => 'chattogram', 'name' => 'কক্সবাজার', 'name_en' => 'coxsbazar', 'order' => 61],
            ['division' => 'chattogram', 'name' => 'খাগড়াছড়ি', 'name_en' => 'khagrachari', 'order' => 62],
            ['division' => 'chattogram', 'name' => 'রাঙ্গামাটি', 'name_en' => 'rangamati', 'order' => 63],
            ['division' => 'chattogram', 'name' => 'বান্দরবান', 'name_en' => 'bandarban', 'order' => 64],
        ];

        foreach ($districts as $district) {
            $division = Division::where('name_en', $district['division'])->first();
            
            if ($division) {
                District::create([
                    'division_id' => $division->id,
                    'name' => $district['name'],
                    'name_en' => $district['name_en'],
                    'order' => $district['order'],
                    // total_seats removed - calculated dynamically from seats
                ]);
            }
        }
    }
}
