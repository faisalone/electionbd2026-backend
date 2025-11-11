<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Party extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'logo',
        'symbol_id',
        'color',
        'founded',
    ];

    protected $with = ['symbol'];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function symbol(): BelongsTo
    {
        return $this->belongsTo(Symbol::class);
    }
}
