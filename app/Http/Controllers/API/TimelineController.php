<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TimelineEvent;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    /**
     * Display election timeline events in chronological order.
     */
    public function index()
    {
        $events = TimelineEvent::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Store - Not needed for read-only timeline.
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Creating timeline events is not allowed via API'
        ], 403);
    }

    /**
     * Show - Single event not needed.
     */
    public function show(string $id)
    {
        $event = TimelineEvent::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    /**
     * Update - Not needed for read-only timeline.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Updating timeline events is not allowed via API'
        ], 403);
    }

    /**
     * Destroy - Not needed for read-only timeline.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Deleting timeline events is not allowed via API'
        ], 403);
    }
}
