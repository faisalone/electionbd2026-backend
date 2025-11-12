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
        // Drop old search_logs table
        Schema::dropIfExists('search_logs');
        
        // Create new search_queries table (one record per unique query)
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query')->unique(); // Unique search term
            $table->unsignedBigInteger('view_count')->default(1); // Total views
            $table->unsignedBigInteger('unique_users')->default(1); // Unique IP count
            $table->timestamp('last_searched_at');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['view_count', 'last_searched_at']); // Popular searches
            $table->index('query'); // Fast lookup
        });
        
        // Create search_query_views table (track each IP's interactions)
        Schema::create('search_query_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_query_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45); // Support IPv6
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('view_count')->default(1); // How many times THIS IP searched
            $table->timestamp('first_viewed_at');
            $table->timestamp('last_viewed_at');
            $table->timestamps();
            
            // Composite unique constraint
            $table->unique(['search_query_id', 'ip_address']);
            
            // Indexes
            $table->index('ip_address');
            $table->index(['search_query_id', 'last_viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_query_views');
        Schema::dropIfExists('search_queries');
        
        // Recreate old search_logs table
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['ip_address', 'created_at']);
            $table->index(['query', 'created_at']);
        });
    }
};
