<?php

namespace App\Services;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use App\Events\VoteCast;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Create a new poll
     */
    public function createPoll(array $data, User $user): Poll
    {
        return DB::transaction(function () use ($data, $user) {
            // Create poll
            $poll = Poll::create([
                'user_id' => $user->id,
                'question' => $data['question'],
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
                'status' => $data['status'] ?? 'pending',
                // uid auto-generated in model boot
            ]);

            // Create options (max 5)
            $options = array_slice($data['options'], 0, 5);
            foreach ($options as $index => $optionData) {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'text' => $optionData['text'],
                    'color' => $optionData['color'] ?? $this->getDefaultColor($index),
                    // votes removed - calculated dynamically
                ]);
            }

            return $poll->load('options');
        });
    }

    /**
     * Vote on a poll
     */
    public function vote(Poll $poll, int $optionId, User $user, string $phoneNumber): bool
    {
        // Check if poll is active
        if (!$poll->isActive()) {
            throw new \Exception('This poll has ended or is not active.');
        }

        // Check if user already voted
        $existingVote = PollVote::where('poll_id', $poll->id)
            ->where('phone_number', $phoneNumber)
            ->first();

        if ($existingVote) {
            throw new \Exception('You have already voted on this poll.');
        }

        // Check if option exists
        $option = PollOption::where('id', $optionId)
            ->where('poll_id', $poll->id)
            ->first();

        if (!$option) {
            throw new \Exception('Invalid poll option.');
        }

        return DB::transaction(function () use ($poll, $option, $user, $phoneNumber) {
            // Create vote
            PollVote::create([
                'poll_id' => $poll->id,
                'poll_option_id' => $option->id,
                'user_id' => $user->id,
                'phone_number' => $phoneNumber,
            ]);

            // Vote counts now calculated dynamically from poll_votes table
            // No need to increment counters

            // Broadcast the vote event
            broadcast(new VoteCast($poll, $option->id))->toOthers();

            return true;
        });
    }

    /**
     * Select winner via lottery from winning option voters
     */
    public function selectWinner(Poll $poll): ?string
    {
        if ($poll->winner_phone) {
            return $poll->winner_phone; // Winner already selected
        }

        // Get the winning option (highest votes) using dynamic vote count
        $winningOption = PollOption::where('poll_id', $poll->id)
            ->withCount('pollVotes')
            ->orderBy('poll_votes_count', 'desc')
            ->first();

        if (!$winningOption || $winningOption->poll_votes_count === 0) {
            return null; // No votes cast
        }

        // Get all voters who voted for the winning option
        $winningVoters = PollVote::where('poll_id', $poll->id)
            ->where('poll_option_id', $winningOption->id)
            ->pluck('phone_number')
            ->toArray();

        if (empty($winningVoters)) {
            return null;
        }

        // Randomly select a winner
        $winnerPhone = $winningVoters[array_rand($winningVoters)];

        // Update poll with winner
        $poll->update([
            'winner_phone' => $winnerPhone,
            'winner_selected_at' => Carbon::now(),
            'status' => 'ended',
        ]);

        // Send notifications
        $this->sendWinnerNotifications($poll, $winnerPhone, $winningVoters);

        Log::info('Poll winner selected', [
            'poll_id' => $poll->id,
            'winner_phone' => $winnerPhone,
            'total_voters' => count($winningVoters),
        ]);

        return $winnerPhone;
    }

    /**
     * End poll and select winner
     */
    public function endPoll(Poll $poll): void
    {
        if ($poll->status === 'ended') {
            return;
        }

        $poll->update(['status' => 'ended']);

        // Select winner
        $this->selectWinner($poll);
    }

    /**
     * Process ended polls
     */
    public function processEndedPolls(): int
    {
        $endedPolls = Poll::where('status', 'active')
            ->where('end_date', '<=', Carbon::now())
            ->get();

        foreach ($endedPolls as $poll) {
            try {
                $this->endPoll($poll);
            } catch (\Exception $e) {
                Log::error('Failed to process ended poll', [
                    'poll_id' => $poll->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $endedPolls->count();
    }

    /**
     * Send winner notifications
     */
    private function sendWinnerNotifications(Poll $poll, string $winnerPhone, array $allVoters): void
    {
        try {
            // Notify winner
            $this->whatsappService->sendPollResultNotification(
                $winnerPhone,
                $poll->question,
                true
            );

            // Notify other participants (optional, can be rate-limited)
            $otherVoters = array_diff($allVoters, [$winnerPhone]);
            $notifyCount = min(count($otherVoters), 10); // Limit to prevent spam

            for ($i = 0; $i < $notifyCount; $i++) {
                $this->whatsappService->sendPollResultNotification(
                    $otherVoters[$i],
                    $poll->question,
                    false
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send winner notifications', [
                'poll_id' => $poll->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get default color for option
     */
    private function getDefaultColor(int $index): string
    {
        $colors = [
            '#C8102E',  // Red
            '#00A651',  // Green
            '#F42A41',  // Pink
            '#06A77D',  // Teal
            '#666666',  // Gray
        ];

        return $colors[$index % count($colors)];
    }
}
