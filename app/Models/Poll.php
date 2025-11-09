<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'creator_name',
        'end_date',
        'status',
        // total_votes removed - use poll_votes_count or votes()->count()
        'winner_phone',
        'winner_selected_at',
    ];

    protected $casts = [
        'end_date' => 'datetime',
        'winner_selected_at' => 'datetime',
    ];

    protected $with = ['options'];
    protected $appends = ['total_votes']; // Add as computed attribute

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Get total votes count dynamically from poll_votes relationship
     */
    public function getTotalVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->status === 'ended' || $this->end_date->isPast();
    }
}
