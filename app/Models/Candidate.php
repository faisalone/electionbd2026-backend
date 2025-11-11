<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Candidate extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'party_id',
        'seat_id',
        'symbol_id',
        'age',
        'education',
        'experience',
        'image',
    ];

    protected $with = ['party', 'seat', 'symbol'];

    protected $appends = ['is_independent'];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function symbol(): BelongsTo
    {
        return $this->belongsTo(Symbol::class);
    }

    // Check if candidate is independent (no party, has symbol)
    protected function isIndependent(): Attribute
    {
        return Attribute::make(
            get: fn() => !$this->party_id && $this->symbol_id
        );
    }

    // Get effective symbol for display
    protected function effectiveSymbol(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Independent candidate: use their assigned symbol
                if (!$this->party_id && $this->symbol) {
                    return [
                        'image' => $this->symbol->image,
                        'symbol_name' => $this->symbol->symbol_name,
                    ];
                }
                // Party candidate: use party's symbol
                if ($this->party) {
                    return [
                        'symbol_name' => $this->party->symbol_name,
                    ];
                }
                return null;
            }
        );
    }
}
