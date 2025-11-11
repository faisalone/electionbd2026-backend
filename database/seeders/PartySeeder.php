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
                'logo' => null,
                'symbol_id' => null, // Symbol to be assigned through admin panel
                'color' => '#00A651',
                'founded' => '১৯৪৯',
            ],
            [
                'name' => 'বাংলাদেশ জাতীয়তাবাদী দল',
                'name_en' => 'bnp',
                'logo' => null,
                'symbol_id' => null, // Symbol to be assigned through admin panel
                'color' => '#00923F',
                'founded' => '১৯৭৮',
            ],
            [
                'name' => 'জাতীয় পার্টি',
                'name_en' => 'jatiya-party',
                'logo' => null,
                'symbol_id' => null, // Symbol to be assigned through admin panel
                'color' => '#F42A41',
                'founded' => '১৯৮৬',
            ],
            [
                'name' => 'জামায়াতে ইসলামী বাংলাদেশ',
                'name_en' => 'jamaat',
                'logo' => null,
                'symbol_id' => null, // Symbol to be assigned through admin panel
                'color' => '#006747',
                'founded' => '১৯৪১',
            ],
        ];

        foreach ($parties as $party) {
            Party::create($party);
        }
    }
}
