<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    protected $fillable = [
        'district_id',
        'seat_number',
        'name',
        'name_en',
        'area',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'seat_number' => 'integer',
    ];

    /**
     * Default ordering by seat number
     */
    protected static function booted()
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('seat_number');
        });
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
