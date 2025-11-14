<?php

namespace App\Console\Commands;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use App\Events\VoteCast;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CastDummyVotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poll:cast-dummy-votes 
                            {poll? : Poll ID or UID to cast votes for (optional, defaults to all active polls)}
                            {--count=10 : Number of votes to cast}
                            {--delay=1 : Delay in seconds between votes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cast dummy votes to active polls with real-time websocket updates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pollIdentifier = $this->argument('poll');
        $voteCount = (int) $this->option('count');
        $delay = (int) $this->option('delay');

        // Get polls to vote on
        if ($pollIdentifier) {
            // Find specific poll by ID or UID
            $poll = Poll::where('id', $pollIdentifier)
                ->orWhere('uid', $pollIdentifier)
                ->first();
            
            if (!$poll) {
                $this->error("Poll not found: {$pollIdentifier}");
                return Command::FAILURE;
            }
            
            $polls = collect([$poll]);
        } else {
            // Get all active polls with upcoming status
            $polls = Poll::where('status', 'active')
                ->orWhere(function ($query) {
                    $query->where('status', 'pending')
                        ->where('end_date', '>', now());
                })
                ->with('options')
                ->get();
            
            if ($polls->isEmpty()) {
                $this->error('No active polls found!');
                return Command::FAILURE;
            }
        }

        $this->info("Found {$polls->count()} poll(s) to vote on");
        $this->info("Will cast {$voteCount} vote(s) with {$delay}s delay between each");
        $this->newLine();

        foreach ($polls as $poll) {
            $this->info("📊 Poll: {$poll->question}");
            $this->info("   Options: " . $poll->options->pluck('text')->implode(', '));
            $this->newLine();
        }

        if (!$this->confirm('Continue?', true)) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $progressBar = $this->output->createProgressBar($voteCount);
        $progressBar->start();

        $voteCastCount = 0;
        $errors = 0;

        for ($i = 0; $i < $voteCount; $i++) {
            try {
                // Pick a random poll
                $poll = $polls->random();
                
                // Pick a random option from this poll
                $option = $poll->options->random();
                
                // Generate random phone number (format: 01XXXXXXXXX)
                $phoneNumber = '01' . rand(300000000, 999999999);
                
                // Create or find user
                $user = User::firstOrCreate(
                    ['phone_number' => $phoneNumber],
                    ['name' => 'Dummy Voter ' . rand(1000, 9999)]
                );

                DB::transaction(function () use ($poll, $option, $user, $phoneNumber) {
                    // Create vote
                    PollVote::create([
                        'poll_id' => $poll->id,
                        'poll_option_id' => $option->id,
                        'user_id' => $user->id,
                        'phone_number' => $phoneNumber,
                    ]);

                    // Broadcast the vote event (this will trigger real-time updates)
                    broadcast(new VoteCast($poll, $option->id))->toOthers();
                });

                $voteCastCount++;
                $progressBar->advance();
                
                // Delay before next vote
                if ($i < $voteCount - 1) {
                    sleep($delay);
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error casting vote: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✅ Successfully cast {$voteCastCount} vote(s)");
        if ($errors > 0) {
            $this->warn("⚠️  {$errors} error(s) occurred");
        }
        
        $this->newLine();
        $this->info('📡 Real-time updates broadcasted via websocket!');
        
        return Command::SUCCESS;
    }
}
