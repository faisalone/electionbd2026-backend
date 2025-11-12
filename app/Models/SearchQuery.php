<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SearchQuery extends Model
{
    protected $fillable = [
        'query',
        'view_count',
        'unique_users',
        'last_searched_at',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'unique_users' => 'integer',
        'last_searched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all views for this query
     */
    public function views(): HasMany
    {
        return $this->hasMany(SearchQueryView::class);
    }

    /**
     * Increment view count and update last searched time
     */
    public function incrementViews(bool $isNewUser = false): void
    {
        $this->increment('view_count');
        
        if ($isNewUser) {
            $this->increment('unique_users');
        }
        
        $this->update(['last_searched_at' => now()]);
    }
}
