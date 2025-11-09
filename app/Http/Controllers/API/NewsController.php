<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a listing of news articles with pagination.
     */
    public function index(Request $request)
    {
        $query = News::query();

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter AI-generated only
        if ($request->has('ai_only') && $request->ai_only) {
            $query->aiGenerated();
        }

        // Sort by latest
        $news = $query->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $news->items(),
            'pagination' => [
                'current_page' => $news->currentPage(),
                'total_pages' => $news->lastPage(),
                'total' => $news->total(),
                'per_page' => $news->perPage(),
            ],
        ]);
    }

    /**
     * Display the specified news article.
     */
    public function show(string $id)
    {
        $news = News::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    /**
     * Store - Not needed (news created via admin or AI).
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Use admin panel to create news'
        ], 403);
    }

    /**
     * Update - Admin only.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Use admin panel to update news'
        ], 403);
    }

    /**
     * Destroy - Admin only.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Use admin panel to delete news'
        ], 403);
    }
}
