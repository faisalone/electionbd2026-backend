<?php

namespace Database\Seeders;

use App\Models\Symbol;
use Illuminate\Database\Seeder;

class SymbolSeeder extends Seeder
{
    /**
     * Seed election symbols from Bangladesh Election Commission
     * Source: Official ECS symbol list
     */
    public function run(): void
    {
        $symbols = [
            ['name' => 'ছাতা', 'name_en' => 'umbrella', 'image' => null],
            ['name' => 'বাইসাইকেল', 'name_en' => 'bicycle', 'image' => null],
            ['name' => 'চাকা', 'name_en' => 'wheel', 'image' => null],
            ['name' => 'গামছা', 'name_en' => 'gamchha', 'image' => null],
            ['name' => 'কাস্তে', 'name_en' => 'sickle', 'image' => null],
            ['name' => 'ধানের শীষ', 'name_en' => 'sheaf-of-paddy', 'image' => null],
            ['name' => 'কবুতর', 'name_en' => 'dove', 'image' => null],
            ['name' => 'কুঁড়েঘর', 'name_en' => 'hut', 'image' => null],
            ['name' => 'হাতুড়ী', 'name_en' => 'hammer', 'image' => null],
            ['name' => 'কুলা', 'name_en' => 'winnowing-fan', 'image' => null],
            ['name' => 'লাঙ্গল', 'name_en' => 'plough', 'image' => null],
            ['name' => 'মশাল', 'name_en' => 'torch', 'image' => null],
            ['name' => 'দাঁড়িপাল্লা', 'name_en' => 'scales', 'image' => null],
            ['name' => 'তারা', 'name_en' => 'star', 'image' => null],
            ['name' => 'গোলাপ ফুল', 'name_en' => 'rose', 'image' => null],
            ['name' => 'মই', 'name_en' => 'ladder', 'image' => null],
            ['name' => 'গরুরগাড়ী', 'name_en' => 'bullock-cart', 'image' => null],
            ['name' => 'ফুলের মালা', 'name_en' => 'garland', 'image' => null],
            ['name' => 'বটগাছ', 'name_en' => 'banyan-tree', 'image' => null],
            ['name' => 'হারিকেন', 'name_en' => 'hurricane-lantern', 'image' => null],
            ['name' => 'আম', 'name_en' => 'mango', 'image' => null],
            ['name' => 'খেজুরগাছ', 'name_en' => 'date-palm', 'image' => null],
            ['name' => 'উদীয়মান সূর্য', 'name_en' => 'rising-sun', 'image' => null],
            ['name' => 'মাছ', 'name_en' => 'fish', 'image' => null],
            ['name' => 'গাভী', 'name_en' => 'cow', 'image' => null],
            ['name' => 'কাঁঠাল', 'name_en' => 'jackfruit', 'image' => null],
            ['name' => 'চেয়ার', 'name_en' => 'chair', 'image' => null],
            ['name' => 'হাতঘড়ি', 'name_en' => 'wristwatch', 'image' => null],
            ['name' => 'মিনার', 'name_en' => 'minaret', 'image' => null],
            ['name' => 'রিক্সা', 'name_en' => 'rickshaw', 'image' => null],
            ['name' => 'হাতপাখা', 'name_en' => 'hand-fan', 'image' => null],
            ['name' => 'মোমবাতি', 'name_en' => 'candle', 'image' => null],
            ['name' => 'হুক্কা', 'name_en' => 'hookah', 'image' => null],
            ['name' => 'কোদাল', 'name_en' => 'spade', 'image' => null],
            ['name' => 'দেওয়াল ঘড়ি', 'name_en' => 'wall-clock', 'image' => null],
            ['name' => 'হাত (পাঞ্জা)', 'name_en' => 'hand', 'image' => null],
            ['name' => 'ছড়ি', 'name_en' => 'walking-stick', 'image' => null],
            ['name' => 'টেলিভিশন', 'name_en' => 'television', 'image' => null],
            ['name' => 'সিংহ', 'name_en' => 'lion', 'image' => null],
            ['name' => 'ডাব', 'name_en' => 'green-coconut', 'image' => null],
            ['name' => 'সোনালী আঁশ', 'name_en' => 'golden-fiber', 'image' => null],
            ['name' => 'আপেল', 'name_en' => 'apple', 'image' => null],
            ['name' => 'মটরগাড়ি (কার)', 'name_en' => 'car', 'image' => null],
            ['name' => 'নোঙ্গর', 'name_en' => 'anchor', 'image' => null],
            ['name' => 'একতারা', 'name_en' => 'ektara', 'image' => null],
            ['name' => 'ঈগল', 'name_en' => 'eagle', 'image' => null],
            ['name' => 'ট্রাক', 'name_en' => 'truck', 'image' => null],
            ['name' => 'কেটলি', 'name_en' => 'kettle', 'image' => null],
            ['name' => 'মাথাল', 'name_en' => 'mathal', 'image' => null],
            ['name' => 'ফুলকপি', 'name_en' => 'cauliflower', 'image' => null],
            ['name' => 'রকেট', 'name_en' => 'rocket', 'image' => null],
            ['name' => 'আনারস', 'name_en' => 'pineapple', 'image' => null],
            ['name' => 'হাতি', 'name_en' => 'elephant', 'image' => null],
            ['name' => 'শাপলার কলি', 'name_en' => 'water-lily-bud', 'image' => null],
        ];

        foreach ($symbols as $symbol) {
            Symbol::create($symbol);
        }
        
        echo "Successfully seeded " . count($symbols) . " symbols.\n";
    }
}
