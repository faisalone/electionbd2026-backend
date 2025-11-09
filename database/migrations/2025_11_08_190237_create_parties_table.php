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
            $table->string('symbol')->nullable(); // Unicode symbol emoji
            $table->string('symbol_name'); // Bengali symbol name
            $table->string('color')->default('#6B7280'); // Hex color code
            $table->string('founded')->nullable(); // Year or date founded
            $table->boolean('is_independent')->default(false); // Special party for independents
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
