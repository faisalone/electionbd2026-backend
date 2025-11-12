<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchQueryView extends Model
{
    protected $fillable = [
        'search_query_id',
        'ip_address',
        'user_agent',
        'view_count',
        'first_viewed_at',
        'last_viewed_at',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the search query that owns this view
     */
    public function searchQuery(): BelongsTo
    {
        return $this->belongsTo(SearchQuery::class);
    }

    /**
     * Increment this IP's view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
    }
}
