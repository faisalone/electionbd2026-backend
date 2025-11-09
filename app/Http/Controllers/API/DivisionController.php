<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Division;

class DivisionController extends Controller
{
    /**
     * Display a listing of all divisions with district counts.
     */
    public function index()
    {
        $divisions = Division::withCount(['districts', 'seats'])
            ->with(['districts' => function ($query) {
                $query->select('id', 'name_en', 'name', 'division_id');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $divisions,
        ]);
    }

    /**
     * Display the specified division with all relationships.
     */
    public function show(string $id)
    {
        $division = Division::with([
            'districts.seats.candidates.party',
            'districts.seats.candidates.symbol'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $division,
        ]);
    }

    /**
     * Store - Not needed for read-only election data.
     */
    public function store()
    {
        return response()->json([
            'success' => false,
            'message' => 'Creating divisions is not allowed via API'
        ], 403);
    }

    /**
     * Update - Not needed for read-only election data.
     */
    public function update()
    {
        return response()->json([
            'success' => false,
            'message' => 'Updating divisions is not allowed via API'
        ], 403);
    }

    /**
     * Destroy - Not needed for read-only election data.
     */
    public function destroy()
    {
        return response()->json([
            'success' => false,
            'message' => 'Deleting divisions is not allowed via API'
        ], 403);
    }
}
