<?php

namespace Database\Seeders;

use App\Models\Party;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $parties = [
            [
                'name' => 'বাংলাদেশ আওয়ামী লীগ',
                'name_en' => 'awami-league',
                'symbol' => '🚢',
                'symbol_name' => 'নৌকা',
                'color' => '#00A651',
                'founded' => '১৯৪৯',
                'is_independent' => false,
            ],
            [
                'name' => 'বাংলাদেশ জাতীয়তাবাদী দল',
                'name_en' => 'bnp',
                'symbol' => '🌾',
                'symbol_name' => 'ধানের শীষ',
                'color' => '#00923F',
                'founded' => '১৯৭৮',
                'is_independent' => false,
            ],
            [
                'name' => 'জাতীয় পার্টি',
                'name_en' => 'jatiya-party',
                'symbol' => '🏏',
                'symbol_name' => 'লাঙ্গল',
                'color' => '#F42A41',
                'founded' => '১৯৮৬',
                'is_independent' => false,
            ],
            [
                'name' => 'জামায়াতে ইসলামী বাংলাদেশ',
                'name_en' => 'jamaat',
                'symbol' => '⚖️',
                'symbol_name' => 'দাঁড়িপাল্লা',
                'color' => '#006747',
                'founded' => '১৯৪১',
                'is_independent' => false,
            ],
            [
                'name' => 'স্বতন্ত্র প্রার্থী',
                'name_en' => 'independent',
                'symbol' => null,
                'symbol_name' => 'বিভিন্ন',
                'color' => '#6B7280',
                'founded' => null,
                'is_independent' => true,
            ],
        ];

        foreach ($parties as $party) {
            Party::create($party);
        }
    }
}
