<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimelineEvent extends Model
{
    protected $fillable = [
        'title',
        'status',
        'date',
        'description',
        'order',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
