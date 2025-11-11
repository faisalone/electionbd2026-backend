<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::with(['party.symbol', 'seat.district.division', 'symbol']);

        if ($request->has('seat_id')) {
            $query->where('seat_id', $request->seat_id);
        }

        if ($request->has('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        // Filter for independent candidates (no party_id)
        if ($request->has('is_independent') && $request->is_independent) {
            $query->whereNull('party_id');
        }

        $candidates = $query->get();

        return response()->json([
            'success' => true,
            'data' => $candidates,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'party_id' => 'nullable|exists:parties,id',
            'seat_id' => 'required|exists:seats,id',
            'symbol_id' => 'nullable|exists:symbols,id',
            'age' => 'required|integer|min:25',
            'education' => 'required|string|max:255',
            'experience' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validation logic: must have either party_id OR symbol_id (for independent)
        if (!$request->party_id && !$request->symbol_id) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate must have either a party or a symbol (for independent candidates)',
            ], 422);
        }

        // If party_id is provided, symbol_id should be null
        if ($request->party_id && $request->symbol_id) {
            return response()->json([
                'success' => false,
                'message' => 'Party candidates cannot have a separate symbol. Symbol is assigned through party.',
            ], 422);
        }

        $data = $request->except('image');

        // Ensure party_id is null for independent candidates
        if (!$request->party_id) {
            $data['party_id'] = null;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('candidates', 'public');
            $data['image'] = Storage::url($path);
        }

        $candidate = Candidate::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Candidate created successfully',
            'data' => $candidate->load(['party', 'seat', 'symbol']),
        ], 201);
    }

    public function show($id)
    {
        $candidate = Candidate::with(['party', 'seat.district.division', 'symbol'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $candidate,
        ]);
    }

    public function update(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255',
            'party_id' => 'nullable|exists:parties,id',
            'seat_id' => 'sometimes|required|exists:seats,id',
            'symbol_id' => 'nullable|exists:symbols,id',
            'age' => 'sometimes|required|integer|min:25',
            'education' => 'sometimes|required|string|max:255',
            'experience' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validation logic: must have either party_id OR symbol_id (for independent)
        $partyId = $request->has('party_id') ? $request->party_id : $candidate->party_id;
        $symbolId = $request->has('symbol_id') ? $request->symbol_id : $candidate->symbol_id;

        if (!$partyId && !$symbolId) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate must have either a party or a symbol (for independent candidates)',
            ], 422);
        }

        // If party_id is provided, symbol_id should be null
        if ($partyId && $symbolId) {
            return response()->json([
                'success' => false,
                'message' => 'Party candidates cannot have a separate symbol. Symbol is assigned through party.',
            ], 422);
        }

        $data = $request->except('image');

        // Ensure party_id is null for independent candidates
        if ($request->has('party_id') && !$request->party_id) {
            $data['party_id'] = null;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($candidate->image) {
                $oldPath = str_replace('/storage/', '', $candidate->image);
                Storage::disk('public')->delete($oldPath);
            }

            $image = $request->file('image');
            $path = $image->store('candidates', 'public');
            $data['image'] = Storage::url($path);
        }

        $candidate->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Candidate updated successfully',
            'data' => $candidate->fresh()->load(['party', 'seat', 'symbol']),
        ]);
    }

    public function destroy($id)
    {
        $candidate = Candidate::findOrFail($id);

        // Delete image if exists
        if ($candidate->image) {
            $oldPath = str_replace('/storage/', '', $candidate->image);
            Storage::disk('public')->delete($oldPath);
        }

        $candidate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Candidate deleted successfully',
        ]);
    }
}
