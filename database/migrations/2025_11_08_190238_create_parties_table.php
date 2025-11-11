<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bengali name
            $table->string('name_en')->unique(); // English name
            $table->string('logo')->nullable(); // Party logo image path
            $table->foreignId('symbol_id')->nullable()->constrained()->onDelete('set null'); // Foreign key to symbols table
            $table->string('color')->default('#6B7280'); // Hex color code
            $table->string('founded')->nullable(); // Year or date founded
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
