<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    /**
     * Display a listing of news articles with pagination.
     */
    public function index(Request $request)
    {
        $query = News::query();

        // Filter by category
        if ($request->has('category') && $request->category !== 'সব') {
            $query->where('category', $request->category);
        }

        // Filter AI-generated only
        if ($request->has('ai_only') && $request->ai_only) {
            $query->where('is_ai_generated', true);
        }

        // Get per_page from request or default
        $perPage = $request->get('per_page', 12);
        
        // Select only necessary fields (exclude source_url for performance)
        // Using Eloquent to get model accessors (image fallback)
        $news = $query->select([
            'id',
            'uid',
            'title',
            'summary',
            'content',
            'image',
            'date',
            'category',
            'is_ai_generated',
            'created_at',
            'updated_at'
        ])
        ->latest('created_at')
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $news->items(),
            'pagination' => [
                'current_page' => $news->currentPage(),
                'total_pages' => $news->lastPage(),
                'total' => $news->total(),
                'per_page' => $news->perPage(),
                'has_more' => $news->hasMorePages(),
            ],
        ]);
    }

    /**
     * Display the specified news article by UID.
     */
    public function show(News $news)
    {
        // Return model directly to get accessors (image fallback)
        // Only load necessary fields to optimize performance
        return response()->json([
            'success' => true,
            'data' => $news->only([
                'id',
                'uid',
                'title',
                'summary',
                'content',
                'image',
                'date',
                'category',
                'is_ai_generated',
                'created_at',
                'updated_at'
            ]),
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
