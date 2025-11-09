<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    /**
     * Display a listing of all parties with candidate counts.
     */
    public function index()
    {
        $parties = Party::withCount('candidates')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parties,
        ]);
    }

    /**
     * Display the specified party with candidates.
     */
    public function show(string $id)
    {
        $party = Party::with(['candidates.seat.district.division'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $party,
        ]);
    }

    /**
     * Store - Not needed for read-only election data.
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Creating parties is not allowed via API'
        ], 403);
    }

    /**
     * Update - Not needed for read-only election data.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Updating parties is not allowed via API'
        ], 403);
    }

    /**
     * Destroy - Not needed for read-only election data.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Deleting parties is not allowed via API'
        ], 403);
    }
}
