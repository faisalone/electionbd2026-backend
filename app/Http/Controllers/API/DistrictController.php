<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    /**
     * Display a listing of districts (optionally filtered by division).
     */
    public function index(Request $request)
    {
        $query = District::withCount('seats')->with(['division', 'seats']);

        if ($request->has('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        $districts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }

    /**
     * Display the specified district with seats and candidates.
     */
    public function show(string $id)
    {
        $district = District::with([
            'division',
            'seats.candidates.party',
            'seats.candidates.symbol'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $district,
        ]);
    }

    /**
     * Store - Not needed for read-only election data.
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Creating districts is not allowed via API'
        ], 403);
    }

    /**
     * Update - Not needed for read-only election data.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Updating districts is not allowed via API'
        ], 403);
    }

    /**
     * Destroy - Not needed for read-only election data.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Deleting districts is not allowed via API'
        ], 403);
    }
}
