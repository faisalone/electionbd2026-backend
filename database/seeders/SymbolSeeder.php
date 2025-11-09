<?php

namespace Database\Seeders;

use App\Models\Symbol;
use Illuminate\Database\Seeder;

class SymbolSeeder extends Seeder
{
    public function run(): void
    {
        $symbols = [
            ['symbol' => '🥭', 'symbol_name' => 'আম', 'is_available' => true],
            ['symbol' => '🍍', 'symbol_name' => 'আনারস', 'is_available' => true],
            ['symbol' => '🍎', 'symbol_name' => 'আপেল', 'is_available' => true],
            ['symbol' => '🍌', 'symbol_name' => 'কলা', 'is_available' => true],
            ['symbol' => '🥥', 'symbol_name' => 'নারিকেল', 'is_available' => true],
            ['symbol' => '⚽', 'symbol_name' => 'ফুটবল', 'is_available' => true],
            ['symbol' => '🚲', 'symbol_name' => 'সাইকেল', 'is_available' => true],
            ['symbol' => '🚗', 'symbol_name' => 'গাড়ি', 'is_available' => true],
            ['symbol' => '✈️', 'symbol_name' => 'বিমান', 'is_available' => true],
            ['symbol' => '📚', 'symbol_name' => 'বই', 'is_available' => true],
            ['symbol' => '⚓', 'symbol_name' => 'নোঙর', 'is_available' => true],
            ['symbol' => '🎯', 'symbol_name' => 'তীর', 'is_available' => true],
            ['symbol' => '🔔', 'symbol_name' => 'ঘন্টা', 'is_available' => true],
            ['symbol' => '🌺', 'symbol_name' => 'ফুল', 'is_available' => true],
            ['symbol' => '⭐', 'symbol_name' => 'তারা', 'is_available' => true],
            ['symbol' => '🏠', 'symbol_name' => 'ঘর', 'is_available' => true],
            ['symbol' => '🌙', 'symbol_name' => 'চাঁদ', 'is_available' => true],
            ['symbol' => '☀️', 'symbol_name' => 'সূর্য', 'is_available' => true],
            ['symbol' => '🔑', 'symbol_name' => 'চাবি', 'is_available' => true],
            ['symbol' => '🎪', 'symbol_name' => 'তাঁবু', 'is_available' => true],
        ];

        foreach ($symbols as $symbol) {
            Symbol::create($symbol);
        }
    }
}
