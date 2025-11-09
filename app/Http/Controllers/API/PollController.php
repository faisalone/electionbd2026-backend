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
     */
    public function index()
    {
        $polls = Poll::with(['options', 'user'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $polls,
        ]);
    }

    /**
     * Display the specified poll with options and vote counts.
     */
    public function show(string $id)
    {
        $poll = Poll::with(['options.pollVotes', 'user'])
            ->withCount('votes')
            ->findOrFail($id);

        // Vote counts are now automatically available via accessor
        // No need to manually add them

        return response()->json([
            'success' => true,
            'data' => $poll,
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
            // Get or create user, update name if user exists
            $user = \App\Models\User::updateOrCreate(
                ['phone_number' => $request->phone_number],
                ['name' => $request->creator_name ?? 'Anonymous']
            );

            $poll = $this->pollService->createPoll([
                'question' => $request->question,
                'creator_name' => $request->creator_name,
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
    public function vote(Request $request, string $pollId)
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

        $poll = Poll::findOrFail($pollId);

        // Verify OTP
        if (!$this->otpService->verify($request->phone_number, $request->otp_code, 'poll_vote', (int)$pollId)) {
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
