<?php

namespace App\Console\Commands;

use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Console\Command;
use Illuminate\Support\Lottery;

class SelectPollWinners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polls:select-winners';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Select lottery winners for ended polls using Laravel Lottery helper';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all polls that have actually ended (end_date has passed AND status is ended) 
        // but don't have a winner yet
        $pollsWithoutWinners = Poll::where('status', 'ended')
            ->where('end_date', '<=', now())
            ->whereDoesntHave('votes', function ($query) {
                $query->where('is_winner', true);
            })
            ->get();

        if ($pollsWithoutWinners->isEmpty()) {
            $this->info('No ended polls without winners found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$pollsWithoutWinners->count()} poll(s) without winners.");

        foreach ($pollsWithoutWinners as $poll) {
            $this->selectWinnerForPoll($poll);
        }

        $this->info('Winner selection completed!');
        return Command::SUCCESS;
    }

    /**
     * Select a random winner from the winning option voters using Laravel Lottery.
     */
    protected function selectWinnerForPoll(Poll $poll): void
    {
        $this->info("Processing poll: {$poll->question}");

        // Get the winning option (option with most votes)
        $winningOption = $poll->options()
            ->withCount('pollVotes')
            ->orderBy('poll_votes_count', 'desc')
            ->first();

        if (!$winningOption || $winningOption->poll_votes_count === 0) {
            $this->warn("  No votes for this poll. Skipping.");
            return;
        }

        // Get all voters who voted for the winning option
        $winningOptionVoters = PollVote::where('poll_id', $poll->id)
            ->where('poll_option_id', $winningOption->id)
            ->get();

        $voterCount = $winningOptionVoters->count();

        if ($voterCount === 0) {
            $this->warn("  No voters for winning option. Skipping.");
            return;
        }

        $this->info("  Winning option: {$winningOption->text} ({$voterCount} voters)");

        // Use Laravel Lottery to select 1 random winner from winning option voters
        // Each voter has equal odds (1 in N where N = total voters)
        $selectedWinner = null;
        
        foreach ($winningOptionVoters as $index => $vote) {
            // Each voter gets equal chance: if they're the "chosen one" in this iteration
            Lottery::odds(1, $voterCount)
                ->winner(function () use ($vote, &$selectedWinner) {
                    $selectedWinner = $vote;
                })
                ->choose();
            
            // If a winner was selected, stop the lottery
            if ($selectedWinner !== null) {
                break;
            }
        }

        // Fallback: if lottery didn't select anyone (shouldn't happen), use random selection
        if ($selectedWinner === null) {
            $selectedWinner = $winningOptionVoters->random();
            $this->warn("  Lottery didn't select a winner, using fallback random selection.");
        }

        // Update the selected winner
        $selectedWinner->is_winner = true;
        $selectedWinner->save();

        $this->info("  ✓ Winner selected: {$selectedWinner->phone_number}");
    }
}
