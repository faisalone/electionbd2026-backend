<?php

namespace Database\Seeders;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PollSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test user for poll creation
        $user = User::firstOrCreate(
            ['email' => 'poll@example.com'],
            [
                'name' => 'Poll Creator',
                'phone_number' => '+8801712345678',
                'password' => bcrypt('password'),
            ]
        );

        // Poll 1: Active poll about election preference
        $poll1 = Poll::create([
            'user_id' => $user->id,
            'question' => 'আগামী নির্বাচনে কোন দল জিতবে বলে আপনি মনে করেন?',
            'end_date' => Carbon::now()->addDays(15),
            'status' => 'active',
            // uid auto-generated
        ]);

        $poll1Options = [
            ['text' => 'বাংলাদেশ আওয়ামী লীগ', 'color' => '#006747'],
            ['text' => 'বাংলাদেশ জাতীয়তাবাদী দল', 'color' => '#00A651'],
            ['text' => 'জাতীয় পার্টি', 'color' => '#FF0000'],
            ['text' => 'স্বতন্ত্র প্রার্থী', 'color' => '#FFA500'],
            ['text' => 'নিশ্চিত নই', 'color' => '#808080'],
        ];

        foreach ($poll1Options as $optionData) {
            $option = PollOption::create([
                'poll_id' => $poll1->id,
                'text' => $optionData['text'],
                'color' => $optionData['color'],
            ]);

            // Add some random votes (5-20 votes per option)
            $voteCount = rand(5, 20);
            for ($i = 0; $i < $voteCount; $i++) {
                PollVote::create([
                    'poll_id' => $poll1->id,
                    'poll_option_id' => $option->id,
                    'user_id' => $user->id,
                    'phone_number' => '+880171' . rand(1000000, 9999999),
                ]);
            }
        }

        // Poll 2: Most important election issue
        $poll2 = Poll::create([
            'user_id' => $user->id,
            'question' => 'নির্বাচনে আপনার কাছে সবচেয়ে গুরুত্বপূর্ণ বিষয় কোনটি?',
            'end_date' => Carbon::now()->addDays(10),
            'status' => 'active',
            // uid auto-generated
        ]);

        $poll2Options = [
            ['text' => 'অর্থনীতি ও কর্মসংস্থান', 'color' => '#1E90FF'],
            ['text' => 'শিক্ষা ও স্বাস্থ্য', 'color' => '#32CD32'],
            ['text' => 'দুর্নীতি প্রতিরোধ', 'color' => '#FF4500'],
            ['text' => 'আইনশৃঙ্খলা', 'color' => '#8B0000'],
        ];

        foreach ($poll2Options as $optionData) {
            $option = PollOption::create([
                'poll_id' => $poll2->id,
                'text' => $optionData['text'],
                'color' => $optionData['color'],
            ]);

            $voteCount = rand(10, 30);
            for ($i = 0; $i < $voteCount; $i++) {
                PollVote::create([
                    'poll_id' => $poll2->id,
                    'poll_option_id' => $option->id,
                    'user_id' => $user->id,
                    'phone_number' => '+880171' . rand(1000000, 9999999),
                ]);
            }
        }

        // Poll 3: Voter turnout prediction
        $poll3 = Poll::create([
            'user_id' => $user->id,
            'question' => 'এবারের নির্বাচনে ভোটার উপস্থিতি কেমন হবে?',
            'end_date' => Carbon::now()->addDays(20),
            'status' => 'active',
            // uid auto-generated
        ]);

        $poll3Options = [
            ['text' => '৮০% এর বেশি', 'color' => '#228B22'],
            ['text' => '৬০-৮০%', 'color' => '#FFD700'],
            ['text' => '৪০-৬০%', 'color' => '#FFA500'],
            ['text' => '৪০% এর কম', 'color' => '#DC143C'],
        ];

        foreach ($poll3Options as $optionData) {
            $option = PollOption::create([
                'poll_id' => $poll3->id,
                'text' => $optionData['text'],
                'color' => $optionData['color'],
            ]);

            $voteCount = rand(8, 25);
            for ($i = 0; $i < $voteCount; $i++) {
                PollVote::create([
                    'poll_id' => $poll3->id,
                    'poll_option_id' => $option->id,
                    'user_id' => $user->id,
                    'phone_number' => '+880171' . rand(1000000, 9999999),
                ]);
            }
        }

        // Poll 4: Ended poll (for testing)
        $poll4 = Poll::create([
            'user_id' => $user->id,
            'question' => 'আগের নির্বাচনে কোন দল জিতেছিল? (পরীক্ষা পোল)',
            'end_date' => Carbon::now()->subDays(2), // Already ended
            'status' => 'ended',
            // uid auto-generated
        ]);

        $poll4Options = [
            ['text' => 'আওয়ামী লীগ', 'color' => '#006747'],
            ['text' => 'বিএনপি', 'color' => '#00A651'],
        ];

        $winningOptionVotes = []; // Track votes for winning option
        foreach ($poll4Options as $index => $optionData) {
            $option = PollOption::create([
                'poll_id' => $poll4->id,
                'text' => $optionData['text'],
                'color' => $optionData['color'],
            ]);

            // First option gets more votes (winning option)
            $voteCount = $index === 0 ? rand(30, 40) : rand(10, 20);
            
            for ($i = 0; $i < $voteCount; $i++) {
                $vote = PollVote::create([
                    'poll_id' => $poll4->id,
                    'poll_option_id' => $option->id,
                    'user_id' => $user->id,
                    'phone_number' => '+880171' . rand(1000000, 9999999),
                    'is_winner' => false, // All start as false
                ]);
                
                // Store votes for the winning option (first option)
                if ($index === 0) {
                    $winningOptionVotes[] = $vote;
                }
            }
        }
        
        // Randomly select ONE winner from the winning option voters
        if (count($winningOptionVotes) > 0) {
            $randomWinner = $winningOptionVotes[array_rand($winningOptionVotes)];
            $randomWinner->is_winner = true;
            $randomWinner->save();
        }

        $this->command->info('Polls seeded successfully with vote counts!');
    }
}
