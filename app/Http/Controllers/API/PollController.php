<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Services\OTPService;
use App\Services\PollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PollController extends Controller
{
    protected $otpService;
    protected $pollService;

    public function __construct(OTPService $otpService, PollService $pollService)
    {
        $this->otpService = $otpService;
        $this->pollService = $pollService;
    }

    /**
     * Display a listing of all polls (both active and ended).
     * Returns only 2 polls: latest active and latest ended.
     */
    public function index()
    {
        $now = now();
        
        // Get the latest active poll (end_date > now)
        $activePoll = Poll::with(['options', 'user'])
            ->where('end_date', '>', $now)
            ->latest()
            ->first();
        
        // Get the latest ended poll (end_date <= now)
        $endedPoll = Poll::with(['options', 'user'])
            ->where('end_date', '<=', $now)
            ->latest()
            ->first();
        
        // Combine both polls
        $polls = collect([$activePoll, $endedPoll])->filter()->values();

        // Add winner information for ended polls
        $polls = $polls->map(function ($poll) use ($now) {
            $pollData = $poll->toArray();
            
            // Add winner if poll has ended
            if ($poll->end_date <= $now && $poll->hasWinner()) {
                $winnerVote = $poll->getWinner();
                $pollData['winner'] = [
                    'phone_number' => $winnerVote->phone_number,
                    'voted_at' => $winnerVote->created_at->toISOString(),
                ];
            } else {
                $pollData['winner'] = null;
            }
            
            return $pollData;
        });

        return response()->json([
            'success' => true,
            'data' => $polls,
        ]);
    }

    /**
     * Display the specified poll with options and vote counts.
     */
    public function show(Poll $poll)
    {
        $poll = $poll->load(['options.pollVotes', 'user'])->loadCount('votes');

        // Add winner information if poll has ended
        $winner = null;
        if ($poll->hasEnded() && $poll->hasWinner()) {
            $winnerVote = $poll->getWinner();
            $winner = [
                'phone_number' => $winnerVote->phone_number,
                'voted_at' => $winnerVote->created_at->toISOString(),
            ];
        }

        $pollData = $poll->toArray();
        $pollData['winner'] = $winner;

        return response()->json([
            'success' => true,
            'data' => $pollData,
        ]);
    }

    /**
     * Create a new poll (requires OTP verification).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'creator_name' => 'nullable|string|max:255',
            'end_date' => 'required|date|after:now',
            'options' => 'required|array|min:2|max:5',
            'options.*.text' => 'required|string|max:255',
            'options.*.color' => 'nullable|string',
            'phone_number' => 'required|string',
            'otp_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify OTP
        if (!$this->otpService->verify($request->phone_number, $request->otp_code, 'poll_create')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code',
            ], 401);
        }

        try {
            // Get or create user
            $user = \App\Models\User::updateOrCreate(
                ['phone_number' => $request->phone_number],
                ['name' => $request->creator_name ?? 'Anonymous']
            );

            $poll = $this->pollService->createPoll([
                'question' => $request->question,
                'end_date' => $request->end_date,
                'options' => $request->options,
            ], $user);

            return response()->json([
                'success' => true,
                'message' => 'Poll created successfully',
                'data' => $poll->load('options'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create poll: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vote on a poll (requires OTP verification).
     */
    public function vote(Request $request, Poll $poll)
    {
        $validator = Validator::make($request->all(), [
            'option_id' => 'required|exists:poll_options,id',
            'phone_number' => 'required|string',
            'otp_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

    // Verify OTP
    if (!$this->otpService->verify($request->phone_number, $request->otp_code, 'poll_vote', (int)$poll->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code',
            ], 401);
        }

        // Check if poll is still active
    if ($poll->hasEnded()) {
            return response()->json([
                'success' => false,
                'message' => 'This poll has ended',
            ], 403);
        }

        try {
            // Get or create user
            $user = \App\Models\User::firstOrCreate(
                ['phone_number' => $request->phone_number],
                ['name' => 'Anonymous Voter']
            );

            $success = $this->pollService->vote($poll, $request->option_id, $user, $request->phone_number);

            return response()->json([
                'success' => true,
                'message' => 'Vote cast successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Send OTP for poll creation or voting.
     */
    public function sendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'purpose' => 'required|in:poll_create,poll_vote',
            'poll_id' => 'nullable|exists:polls,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $otp = $this->otpService->generateAndSend(
                $request->phone_number,
                $request->purpose,
                $request->poll_id
            );

            if (!$otp) {
                throw new \Exception('Failed to send OTP');
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully to WhatsApp',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP code (optional endpoint for pre-validation).
     */
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'otp_code' => 'required|string',
            'purpose' => 'required|in:poll_create,poll_vote',
            'poll_id' => 'nullable|exists:polls,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $isValid = $this->otpService->verify(
            $request->phone_number,
            $request->otp_code,
            $request->purpose,
            $request->poll_id
        );

        return response()->json([
            'success' => $isValid,
            'message' => $isValid ? 'OTP verified successfully' : 'Invalid or expired OTP',
        ], $isValid ? 200 : 401);
    }

    /**
     * Get winner ranking - Shows the single winner and list of all voters who voted for the winning option
     */
    public function getWinnerRanking(Poll $poll)
    {
        if (now()->lt($poll->end_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Poll has not ended yet',
            ], 400);
        }

        // Find the winning option (option with most votes)
        $winningOption = $poll->options()
            ->withCount('pollVotes')
            ->orderBy('poll_votes_count', 'desc')
            ->first();

        if (!$winningOption) {
            return response()->json([
                'success' => false,
                'message' => 'No votes found',
            ], 404);
        }

        // Get the single winner (is_winner = true)
        $singleWinner = $poll->votes()
            ->where('is_winner', true)
            ->first();

        // Get all voters who voted for the winning option, ordered by vote time
        $winningOptionVoters = $winningOption->pollVotes()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($vote, $index) {
                return [
                    'phone_number' => $vote->phone_number,
                    'voted_at' => $vote->created_at->toISOString(),
                    'rank' => $index + 1,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'winner' => $singleWinner ? [
                    'phone_number' => $singleWinner->phone_number,
                    'voted_at' => $singleWinner->created_at->toISOString(),
                ] : null,
                'winning_option' => [
                    'id' => $winningOption->id,
                    'text' => $winningOption->text,
                    'color' => $winningOption->color,
                    'votes' => $winningOption->poll_votes_count,
                ],
                'winning_option_voters' => $winningOptionVoters,
            ],
        ]);
    }

    /**
     * Update - Not allowed.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Updating polls is not allowed'
        ], 403);
    }

    /**
     * Destroy - Not allowed.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Deleting polls is not allowed'
        ], 403);
    }
}
