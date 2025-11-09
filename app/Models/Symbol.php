<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Symbol extends Model
{
    protected $fillable = [
        'symbol',
        'symbol_name',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
