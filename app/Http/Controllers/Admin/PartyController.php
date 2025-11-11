<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PartyController extends Controller
{
    public function index()
    {
        $parties = Party::withCount('candidates')->get();

        return response()->json([
            'success' => true,
            'data' => $parties,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_en' => 'required|string|max:255|unique:parties,name_en',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'symbol_id' => 'nullable|exists:symbols,id',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'founded' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except('logo');

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('parties', 'public');
            $data['logo'] = Storage::url($logoPath);
        }

        $party = Party::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Party created successfully',
            'data' => $party,
        ], 201);
    }

    public function show($id)
    {
        $party = Party::with('candidates')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $party,
        ]);
    }

    public function update(Request $request, $id)
    {
        $party = Party::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255|unique:parties,name_en,' . $id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'symbol_id' => 'nullable|exists:symbols,id',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'founded' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except('logo');

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($party->logo) {
                $oldPath = str_replace('/storage/', '', $party->logo);
                Storage::disk('public')->delete($oldPath);
            }
            $logoPath = $request->file('logo')->store('parties', 'public');
            $data['logo'] = Storage::url($logoPath);
        }

        $party->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Party updated successfully',
            'data' => $party->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $party = Party::findOrFail($id);
        $party->delete();

        return response()->json([
            'success' => true,
            'message' => 'Party deleted successfully',
        ]);
    }
}
