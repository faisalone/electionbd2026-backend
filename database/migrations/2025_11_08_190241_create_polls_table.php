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
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 16)->unique()->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator
            $table->text('question'); // Poll question
            $table->dateTime('end_date')->nullable(); // Poll end date and time
            $table->enum('status', ['pending', 'active', 'ended', 'rejected'])->default('active');
            // total_votes removed - calculated dynamically from poll_votes relationship
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
