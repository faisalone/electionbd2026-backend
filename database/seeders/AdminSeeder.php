<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'Admin',
            'phone_number' => '01710541719', // Replace with your actual WhatsApp number
            'is_active' => true,
            'role' => 'super_admin',
        ]);

        $this->command->info('Admin seeded successfully!');
    }
}
