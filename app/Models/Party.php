<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'symbol',
        'symbol_name',
        'color',
        'founded',
        'is_independent',
    ];

    protected $casts = [
        'is_independent' => 'boolean',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public static function independent()
    {
        return static::where('is_independent', true)->first();
    }
}
