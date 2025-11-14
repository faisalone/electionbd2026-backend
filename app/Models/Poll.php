<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Poll extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'uid',
        'end_date',
        'status',
        // total_votes removed - use poll_votes_count or votes()->count()
    ];

    protected $casts = [
        'end_date' => 'datetime',
    ];

    protected $with = ['options', 'user'];
    protected $appends = ['total_votes']; // Add as computed attribute

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship - represents the poll creator
     */
    public function creator(): BelongsTo
    {
        return $this->user();
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

    /**
     * Get the single winner for this poll
     */
    public function getWinner()
    {
        return $this->votes()->where('is_winner', true)->first();
    }

    /**
     * Check if poll has a winner selected
     */
    public function hasWinner(): bool
    {
        return $this->votes()->where('is_winner', true)->exists();
    }

    /**
     * Select a random winner from winning option voters using Laravel Lottery
     */
    public function selectWinner(): ?PollVote
    {
        // Get the winning option (option with most votes)
        $winningOption = $this->options()
            ->withCount('pollVotes')
            ->orderBy('poll_votes_count', 'desc')
            ->first();

        if (!$winningOption || $winningOption->poll_votes_count === 0) {
            return null;
        }

        // Get all voters who voted for the winning option
        $winningOptionVoters = $this->votes()
            ->where('poll_option_id', $winningOption->id)
            ->get();

        if ($winningOptionVoters->isEmpty()) {
            return null;
        }

        // Use Laravel Lottery to select 1 random winner
        // Simple approach: randomly select one from the collection
        $selectedWinner = $winningOptionVoters->random();

        // Update the selected winner
        $selectedWinner->is_winner = true;
        $selectedWinner->save();

        return $selectedWinner;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date && $this->end_date->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->status === 'ended' || ($this->end_date && $this->end_date->isPast());
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uid)) {
                // Generate a short, url-safe, opaque identifier (base62 ~10 chars)
                $model->uid = static::generateUid();
            }
        });
    }

    /**
     * Override route key name to use uid for implicit route-model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    /**
     * Generate a unique base62 uid. Retries on collision (very low probability).
     */
    protected static function generateUid(int $length = 10): string
    {
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $uid = '';
            for ($i = 0; $i < $length; $i++) {
                $uid .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('uid', $uid)->exists());
        return $uid;
    }
}
