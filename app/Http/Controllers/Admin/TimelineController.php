<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimelineEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimelineController extends Controller
{
    public function index()
    {
        $events = TimelineEvent::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'date' => 'required|string|max:255',
            'description' => 'required|string',
            'order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $event = TimelineEvent::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Timeline event created successfully',
            'data' => $event,
        ], 201);
    }

    public function show($id)
    {
        $event = TimelineEvent::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    public function update(Request $request, $id)
    {
        $event = TimelineEvent::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'order' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $event->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Timeline event updated successfully',
            'data' => $event->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $event = TimelineEvent::findOrFail($id);
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Timeline event deleted successfully',
        ]);
    }
}
