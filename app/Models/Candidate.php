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

    protected function effectiveSymbol(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->party && $this->party->is_independent && $this->symbol) {
                    return [
                        'symbol' => $this->symbol->symbol,
                        'symbol_name' => $this->symbol->symbol_name,
                    ];
                }
                return [
                    'symbol' => $this->party->symbol ?? '',
                    'symbol_name' => $this->party->symbol_name ?? '',
                ];
            }
        );
    }
}
