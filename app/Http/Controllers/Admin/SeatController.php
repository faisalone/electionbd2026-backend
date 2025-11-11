<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeatController extends Controller
{
    public function index(Request $request)
    {
        $query = Seat::with(['district.division']);

        if ($request->has('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        $seats = $query->orderBy('name_en')->get();

        return response()->json([
            'success' => true,
            'data' => $seats,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'district_id' => 'required|exists:districts,id',
            'name' => 'required|string|max:255',
            'name_en' => 'required|string|max:255|unique:seats,name_en',
            'area' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $seat = Seat::create($request->only(['district_id', 'name', 'name_en', 'area']));

        return response()->json([
            'success' => true,
            'message' => 'Seat created successfully',
            'data' => $seat->load('district'),
        ], 201);
    }

    public function show($id)
    {
        $seat = Seat::with(['district.division', 'candidates'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $seat,
        ]);
    }

    public function update(Request $request, $id)
    {
        $seat = Seat::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'district_id' => 'sometimes|required|exists:districts,id',
            'name' => 'sometimes|required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255|unique:seats,name_en,' . $id,
            'area' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $seat->update($request->only(['district_id', 'name', 'name_en', 'area']));

        return response()->json([
            'success' => true,
            'message' => 'Seat updated successfully',
            'data' => $seat->fresh()->load('district'),
        ]);
    }

    public function destroy($id)
    {
        $seat = Seat::findOrFail($id);
        $seat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seat deleted successfully',
        ]);
    }
}
