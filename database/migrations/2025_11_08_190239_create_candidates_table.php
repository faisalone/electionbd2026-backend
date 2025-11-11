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
            $table->foreignId('party_id')->nullable()->constrained()->onDelete('cascade'); // Nullable for independents
            $table->foreignId('seat_id')->constrained()->onDelete('cascade');
            $table->foreignId('symbol_id')->nullable()->constrained()->onDelete('set null'); // For independent candidates only
            $table->integer('age');
            $table->string('education');
            $table->text('experience')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            // Ensure unique combination: one candidate per seat per party OR one independent candidate per seat per symbol
            $table->unique(['seat_id', 'party_id', 'symbol_id']);
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
