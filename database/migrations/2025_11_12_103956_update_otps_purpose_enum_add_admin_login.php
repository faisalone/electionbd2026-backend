<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to add 'admin_login'
        DB::statement("ALTER TABLE otps MODIFY COLUMN purpose ENUM('poll_create', 'poll_vote', 'admin_login') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE otps MODIFY COLUMN purpose ENUM('poll_create', 'poll_vote') NOT NULL");
    }
};
