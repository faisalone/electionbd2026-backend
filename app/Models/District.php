<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = [
        'division_id',
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

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    /**
     * Get total seats count dynamically from seats relationship
     */
    public function getTotalSeatsAttribute(): int
    {
        return $this->seats()->count();
    }
}
