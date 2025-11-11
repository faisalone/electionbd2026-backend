<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Symbol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SymbolController extends Controller
{
    public function index()
    {
        $symbols = Symbol::withCount('candidates')->get();

        return response()->json([
            'success' => true,
            'data' => $symbols,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
            'symbol_name' => 'required|string|max:255',
            'is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except('image');

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('symbols', 'public');
            $data['image'] = Storage::url($imagePath);
        }

        $symbol = Symbol::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Symbol created successfully',
            'data' => $symbol,
        ], 201);
    }

    public function show($id)
    {
        $symbol = Symbol::with('candidates')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $symbol,
        ]);
    }

    public function update(Request $request, $id)
    {
        $symbol = Symbol::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'symbol_name' => 'sometimes|required|string|max:255',
            'is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except('image');

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($symbol->image) {
                $oldPath = str_replace('/storage/', '', $symbol->image);
                Storage::disk('public')->delete($oldPath);
            }
            
            $imagePath = $request->file('image')->store('symbols', 'public');
            $data['image'] = Storage::url($imagePath);
        }

        $symbol->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Symbol updated successfully',
            'data' => $symbol->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $symbol = Symbol::findOrFail($id);
        
        // Delete image if exists
        if ($symbol->image) {
            $oldPath = str_replace('/storage/', '', $symbol->image);
            Storage::disk('public')->delete($oldPath);
        }
        
        $symbol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Symbol deleted successfully',
        ]);
    }
}
