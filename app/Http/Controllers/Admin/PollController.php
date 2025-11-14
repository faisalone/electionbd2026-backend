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
            'end_date' => 'nullable|date',
            'status' => 'sometimes|required|in:pending,active,ended,rejected',
            'options' => 'sometimes|array|min:2|max:5',
            'options.*.id' => 'sometimes|exists:poll_options,id',
            'options.*.text' => 'required_with:options|string',
            'options.*.color' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update poll
        $poll->update($request->only(['question', 'end_date', 'status']));

        // Update options if provided
        if ($request->has('options')) {
            foreach ($request->options as $optionData) {
                if (isset($optionData['id'])) {
                    // Update existing option
                    $option = $poll->options()->find($optionData['id']);
                    if ($option) {
                        $option->update([
                            'text' => $optionData['text'],
                            'color' => $optionData['color'] ?? $option->color,
                        ]);
                    }
                } else {
                    // Create new option
                    $poll->options()->create([
                        'text' => $optionData['text'],
                        'color' => $optionData['color'] ?? '#3b82f6',
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Poll updated successfully',
            'data' => $poll->fresh(['options']),
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
