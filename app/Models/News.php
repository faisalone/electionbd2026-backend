<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'summary',
        'content',
        'image',
        'date',
        'category',
        'is_ai_generated',
        'source_url',
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
    ];

    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
