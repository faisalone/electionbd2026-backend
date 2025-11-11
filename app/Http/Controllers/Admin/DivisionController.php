<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DivisionController extends Controller
{
    /**
     * Display a listing of divisions
     */
    public function index()
    {
        $divisions = Division::withCount(['districts', 'seats'])->get();

        return response()->json([
            'success' => true,
            'data' => $divisions,
        ]);
    }

    /**
     * Store a newly created division
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_en' => 'required|string|max:255|unique:divisions,name_en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $division = Division::create($request->only(['name', 'name_en']));

        return response()->json([
            'success' => true,
            'message' => 'Division created successfully',
            'data' => $division,
        ], 201);
    }

    /**
     * Display the specified division
     */
    public function show($id)
    {
        $division = Division::with(['districts', 'seats'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $division,
        ]);
    }

    /**
     * Update the specified division
     */
    public function update(Request $request, $id)
    {
        $division = Division::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255|unique:divisions,name_en,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $division->update($request->only(['name', 'name_en']));

        return response()->json([
            'success' => true,
            'message' => 'Division updated successfully',
            'data' => $division->fresh(),
        ]);
    }

    /**
     * Remove the specified division
     */
    public function destroy($id)
    {
        $division = Division::findOrFail($id);
        $division->delete();

        return response()->json([
            'success' => true,
            'message' => 'Division deleted successfully',
        ]);
    }
}
