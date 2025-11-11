<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    protected $fillable = [
        'title',
        'uid',
        'summary',
        'content',
        'image',
        'date',
        'category',
        'is_ai_generated',
        'status',
        'source_url',
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
    ];

    /**
     * Get the image URL with fallback to placeholder.
     */
    public function getImageAttribute($value): string
    {
        return $value ?? '/news-placeholder.svg';
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = static::generateUid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    protected static function generateUid(int $length = 10): string
    {
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $uid = '';
            for ($i = 0; $i < $length; $i++) {
                $uid .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('uid', $uid)->exists());
        return $uid;
    }
}
