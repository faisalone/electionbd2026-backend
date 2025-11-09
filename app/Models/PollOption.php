<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    protected $fillable = [
        'poll_id',
        'text',
        'color',
        // votes removed - use vote_count or pollVotes()->count()
    ];

    protected $appends = ['vote_count']; // Add as computed attribute

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Get vote count dynamically from poll_votes relationship
     */
    public function getVoteCountAttribute(): int
    {
        return $this->pollVotes()->count();
    }
}
