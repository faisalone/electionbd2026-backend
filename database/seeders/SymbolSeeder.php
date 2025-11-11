<?php

namespace Database\Seeders;

use App\Models\Symbol;
use Illuminate\Database\Seeder;

class SymbolSeeder extends Seeder
{
    public function run(): void
    {
        // Symbols now require image uploads, so no default data
        // Admin can add symbols through the admin panel
        
        echo "Symbol table created. Add symbols through admin panel.\n";
    }
}
