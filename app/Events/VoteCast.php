<?php

namespace App\Events;

use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteCast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $poll;
    public $optionId;
    public $totalVotes;
    public $optionVotes;

    /**
     * Create a new event instance.
     */
    public function __construct(Poll $poll, int $optionId)
    {
        $this->poll = $poll;
        $this->optionId = $optionId;
        
        // Calculate total votes for this poll
        $this->totalVotes = $poll->votes()->count();
        
        // Get vote count for each option
        $this->optionVotes = PollOption::where('poll_id', $poll->id)
            ->withCount('pollVotes')
            ->get()
            ->mapWithKeys(function ($option) {
                return [$option->id => $option->poll_votes_count];
            })
            ->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('poll.' . $this->poll->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'vote.cast';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'poll_id' => $this->poll->id,
            'option_id' => $this->optionId,
            'total_votes' => $this->totalVotes,
            'option_votes' => $this->optionVotes,
        ];
    }
}
