<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    /**
     * Display a listing of candidates (optionally filtered).
     */
    public function index(Request $request)
    {
        $query = Candidate::with(['party', 'seat.district.division', 'symbol']);

        // Filter by seat
        if ($request->has('seat_id')) {
            $query->where('seat_id', $request->seat_id);
        }

        // Filter by party
        if ($request->has('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        // Filter by district
        if ($request->has('district_id')) {
            $query->whereHas('seat', function($q) use ($request) {
                $q->where('district_id', $request->district_id);
            });
        }

        // Filter by division
        if ($request->has('division_id')) {
            $query->whereHas('seat.district', function($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }

        // Filter by independent candidates
        if ($request->has('is_independent')) {
            $query->whereHas('party', function($q) {
                $q->where('is_independent', true);
            });
        }

        // Search by name (supports both Bengali and English)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_en', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        // Order by seat number first (so candidates are grouped by seat), then by candidate name
        // Use whereHas to ensure seat relationship exists, then order by seat_number via subquery
        $query->orderByRaw('(SELECT seat_number FROM seats WHERE seats.id = candidates.seat_id) ASC')
              ->orderBy('candidates.name', 'asc');

        // Pagination support
        if ($request->has('per_page')) {
            $perPage = min((int)$request->per_page, 100); // Max 100 per page
            $candidates = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $candidates->items(),
                'pagination' => [
                    'total' => $candidates->total(),
                    'per_page' => $candidates->perPage(),
                    'current_page' => $candidates->currentPage(),
                    'last_page' => $candidates->lastPage(),
                ],
            ]);
        }

        $candidates = $query->get();

        return response()->json([
            'success' => true,
            'data' => $candidates,
        ]);
    }

    /**
     * Display the specified candidate with full details.
     */
    public function show(string $id)
    {
        $candidate = Candidate::with([
            'party',
            'seat.district.division',
            'symbol'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $candidate,
        ]);
    }

    /**
     * Store - Not needed for read-only election data.
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Creating candidates is not allowed via API'
        ], 403);
    }

    /**
     * Update - Not needed for read-only election data.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Updating candidates is not allowed via API'
        ], 403);
    }

    /**
     * Destroy - Not needed for read-only election data.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Deleting candidates is not allowed via API'
        ], 403);
    }
}
