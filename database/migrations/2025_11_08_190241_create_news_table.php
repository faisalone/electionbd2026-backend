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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Bengali title
            $table->text('summary'); // Short summary
            $table->longText('content'); // Full content
            $table->string('image')->nullable();
            $table->string('date'); // Bengali date
            $table->string('category')->default('নির্বাচন');
            $table->boolean('is_ai_generated')->default(false);
            $table->text('source_url')->nullable(); // Original source if applicable
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
