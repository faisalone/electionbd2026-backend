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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bengali name
            $table->string('name_en'); // English name
            $table->foreignId('party_id')->constrained()->onDelete('cascade');
            $table->foreignId('seat_id')->constrained()->onDelete('cascade');
            $table->foreignId('symbol_id')->nullable()->constrained()->onDelete('set null'); // For independent candidates
            $table->integer('age');
            $table->string('education');
            $table->text('experience')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            // Ensure one candidate per seat per party (or independent with unique symbol)
            $table->unique(['seat_id', 'party_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
