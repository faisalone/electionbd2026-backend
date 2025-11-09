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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('otp_code');
            $table->enum('purpose', ['poll_create', 'poll_vote']); // What the OTP is for
            $table->foreignId('poll_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_verified')->default(false);
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index(['phone_number', 'otp_code', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
