<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DistrictController extends Controller
{
    public function index(Request $request)
    {
        $query = District::with('division')->withCount('seats');

        if ($request->has('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        $districts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'division_id' => 'required|exists:divisions,id',
            'name' => 'required|string|max:255',
            'name_en' => 'required|string|max:255|unique:districts,name_en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $district = District::create($request->only(['division_id', 'name', 'name_en']));

        return response()->json([
            'success' => true,
            'message' => 'District created successfully',
            'data' => $district->load('division'),
        ], 201);
    }

    public function show($id)
    {
        $district = District::with(['division', 'seats'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $district,
        ]);
    }

    public function update(Request $request, $id)
    {
        $district = District::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'division_id' => 'sometimes|required|exists:divisions,id',
            'name' => 'sometimes|required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255|unique:districts,name_en,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $district->update($request->only(['division_id', 'name', 'name_en']));

        return response()->json([
            'success' => true,
            'message' => 'District updated successfully',
            'data' => $district->fresh()->load('division'),
        ]);
    }

    public function destroy($id)
    {
        $district = District::findOrFail($id);
        $district->delete();

        return response()->json([
            'success' => true,
            'message' => 'District deleted successfully',
        ]);
    }
}
