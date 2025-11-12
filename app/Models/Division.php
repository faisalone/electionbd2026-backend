<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Division extends Model
{
    protected $fillable = [
        'order',
        'name',
        'name_en',
        // total_seats removed - use seats_count or seats()->count()
    ];

    protected $appends = ['total_seats']; // Add as computed attribute for backward compatibility

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Default ordering by order number
     */
    protected static function booted()
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('order');
        });
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function seats(): HasManyThrough
    {
        return $this->hasManyThrough(Seat::class, District::class);
    }

    /**
     * Get total seats count dynamically from seats relationship
     */
    public function getTotalSeatsAttribute(): int
    {
        return $this->seats()->count();
    }
}
