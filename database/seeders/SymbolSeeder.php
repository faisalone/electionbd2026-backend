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
            ['symbol_name' => 'ছাতা', 'image' => 'symbols/umbrella.png'],
            ['symbol_name' => 'বাইসাইকেল', 'image' => 'symbols/bicycle.png'],
            ['symbol_name' => 'চাকা', 'image' => 'symbols/wheel.png'],
            ['symbol_name' => 'গামছা', 'image' => 'symbols/gamchha.png'],
            ['symbol_name' => 'কাস্তে', 'image' => 'symbols/sickle.png'],
            ['symbol_name' => 'ধানের শীষ', 'image' => 'symbols/sheaf-of-paddy.png'],
            ['symbol_name' => 'কবুতর', 'image' => 'symbols/dove.png'],
            ['symbol_name' => 'কুঁড়েঘর', 'image' => 'symbols/hut.png'],
            ['symbol_name' => 'হাতুড়ী', 'image' => 'symbols/hammer.png'],
            ['symbol_name' => 'কুলা', 'image' => 'symbols/winnowing-fan.png'],
            ['symbol_name' => 'লাঙ্গল', 'image' => 'symbols/plough.png'],
            ['symbol_name' => 'মশাল', 'image' => 'symbols/torch.png'],
            ['symbol_name' => 'দাঁড়িপাল্লা', 'image' => 'symbols/scales.png'],
            ['symbol_name' => 'তারা', 'image' => 'symbols/star.png'],
            ['symbol_name' => 'গোলাপ ফুল', 'image' => 'symbols/rose.png'],
            ['symbol_name' => 'মই', 'image' => 'symbols/ladder.png'],
            ['symbol_name' => 'গরুরগাড়ী', 'image' => 'symbols/bullock-cart.png'],
            ['symbol_name' => 'ফুলের মালা', 'image' => 'symbols/garland.png'],
            ['symbol_name' => 'বটগাছ', 'image' => 'symbols/banyan-tree.png'],
            ['symbol_name' => 'হারিকেন', 'image' => 'symbols/hurricane-lantern.png'],
            ['symbol_name' => 'আম', 'image' => 'symbols/mango.png'],
            ['symbol_name' => 'খেজুরগাছ', 'image' => 'symbols/date-palm.png'],
            ['symbol_name' => 'উদীয়মান সূর্য', 'image' => 'symbols/rising-sun.png'],
            ['symbol_name' => 'মাছ', 'image' => 'symbols/fish.png'],
            ['symbol_name' => 'গাভী', 'image' => 'symbols/cow.png'],
            ['symbol_name' => 'কাঁঠাল', 'image' => 'symbols/jackfruit.png'],
            ['symbol_name' => 'চেয়ার', 'image' => 'symbols/chair.png'],
            ['symbol_name' => 'হাতঘড়ি', 'image' => 'symbols/wristwatch.png'],
            ['symbol_name' => 'মিনার', 'image' => 'symbols/minaret.png'],
            ['symbol_name' => 'রিক্সা', 'image' => 'symbols/rickshaw.png'],
            ['symbol_name' => 'হাতপাখা', 'image' => 'symbols/hand-fan.png'],
            ['symbol_name' => 'মোমবাতি', 'image' => 'symbols/candle.png'],
            ['symbol_name' => 'হুক্কা', 'image' => 'symbols/hookah.png'],
            ['symbol_name' => 'কোদাল', 'image' => 'symbols/spade.png'],
            ['symbol_name' => 'দেওয়াল ঘড়ি', 'image' => 'symbols/wall-clock.png'],
            ['symbol_name' => 'হাত (পাঞ্জা)', 'image' => 'symbols/hand.png'],
            ['symbol_name' => 'ছড়ি', 'image' => 'symbols/walking-stick.png'],
            ['symbol_name' => 'টেলিভিশন', 'image' => 'symbols/television.png'],
            ['symbol_name' => 'সিংহ', 'image' => 'symbols/lion.png'],
            ['symbol_name' => 'ডাব', 'image' => 'symbols/green-coconut.png'],
            ['symbol_name' => 'সোনালী আঁশ', 'image' => 'symbols/golden-fiber.png'],
            ['symbol_name' => 'আপেল', 'image' => 'symbols/apple.png'],
            ['symbol_name' => 'মটরগাড়ি (কার)', 'image' => 'symbols/car.png'],
            ['symbol_name' => 'নোঙ্গর', 'image' => 'symbols/anchor.png'],
            ['symbol_name' => 'একতারা', 'image' => 'symbols/ektara.png'],
            ['symbol_name' => 'ঈগল', 'image' => 'symbols/eagle.png'],
            ['symbol_name' => 'ট্রাক', 'image' => 'symbols/truck.png'],
            ['symbol_name' => 'কেটলি', 'image' => 'symbols/kettle.png'],
            ['symbol_name' => 'মাথাল', 'image' => 'symbols/mathal.png'],
            ['symbol_name' => 'ফুলকপি', 'image' => 'symbols/cauliflower.png'],
            ['symbol_name' => 'রকেট', 'image' => 'symbols/rocket.png'],
            ['symbol_name' => 'আনারস', 'image' => 'symbols/pineapple.png'],
            ['symbol_name' => 'হাতি', 'image' => 'symbols/elephant.png'],
            ['symbol_name' => 'শাপলার কলি', 'image' => 'symbols/water-lily-bud.png'],
        ];

        foreach ($symbols as $symbol) {
            Symbol::create($symbol);
        }
        
        echo "Successfully seeded " . count($symbols) . " symbols.\n";
    }
}
