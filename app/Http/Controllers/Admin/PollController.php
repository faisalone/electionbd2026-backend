<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PollController extends Controller
{
    public function index()
    {
        $polls = Poll::with(['user', 'options'])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $polls,
        ]);
    }

    public function show($id)
    {
        $poll = Poll::with(['user', 'options.pollVotes', 'votes'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $poll,
        ]);
    }

    public function update(Request $request, $id)
    {
        $poll = Poll::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'question' => 'sometimes|required|string',
            'end_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:active,ended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $poll->update($request->only(['question', 'end_date', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Poll updated successfully',
            'data' => $poll->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $poll = Poll::findOrFail($id);
        $poll->delete();

        return response()->json([
            'success' => true,
            'message' => 'Poll deleted successfully',
        ]);
    }

    /**
     * Get all votes for a specific poll
     */
    public function votes($id)
    {
        $poll = Poll::findOrFail($id);
        $votes = PollVote::where('poll_id', $id)
            ->with(['pollOption', 'user'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'poll' => $poll,
            'votes' => $votes,
        ]);
    }

    /**
     * Manually select winner for a poll
     */
    public function selectWinner(Request $request, $id)
    {
        $poll = Poll::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'count' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Reset all winners first
        PollVote::where('poll_id', $poll->id)->update(['is_winner' => false]);

        // Select winner(s)
        $winner = $poll->selectWinner();

        if (!$winner) {
            return response()->json([
                'success' => false,
                'message' => 'No votes to select winner from',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Winner selected successfully',
            'winner' => $winner->load(['user', 'pollOption']),
        ]);
    }

    /**
     * End poll manually
     */
    public function endPoll($id)
    {
        $poll = Poll::findOrFail($id);
        $poll->update(['status' => 'ended']);

        return response()->json([
            'success' => true,
            'message' => 'Poll ended successfully',
            'data' => $poll->fresh(),
        ]);
    }
}
