<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use Illuminate\Http\Request;

class SeatController extends Controller
{
    /**
     * Display a listing of seats (optionally filtered by district or division).
     */
    public function index(Request $request)
    {
        $query = Seat::with(['district.division', 'candidates.party', 'candidates.symbol']);

        if ($request->has('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->has('division_id')) {
            $query->whereHas('district', function($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }

        $seats = $query->orderBy('seat_number')->get();

        return response()->json([
            'success' => true,
            'data' => $seats,
        ]);
    }

    /**
     * Display the specified seat with all candidates.
     */
    public function show(string $id)
    {
        $seat = Seat::with([
            'district.division',
            'candidates.party',
            'candidates.symbol'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $seat,
        ]);
    }

    /**
     * Store - Not needed for read-only election data.
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Creating seats is not allowed via API'
        ], 403);
    }

    /**
     * Update - Not needed for read-only election data.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Updating seats is not allowed via API'
        ], 403);
    }

    /**
     * Destroy - Not needed for read-only election data.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Deleting seats is not allowed via API'
        ], 403);
    }
}
