<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\NewsGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class NewsController extends Controller
{
    protected $newsGenerationService;

    public function __construct(NewsGenerationService $newsGenerationService)
    {
        $this->newsGenerationService = $newsGenerationService;
    }

    public function index(Request $request)
    {
        $query = News::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $news = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'date' => 'nullable|date', // Made optional - defaults to today if not provided
            'category' => 'required|string',
            'is_ai_generated' => 'boolean',
            'status' => 'nullable|in:published,pending,rejected',
            'source_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except('image');
        
        // Auto-fill date if not provided
        if (!$request->has('date') || empty($request->date)) {
            $data['date'] = now()->format('Y-m-d');
        }
        
        // Default status to 'pending' if not provided (unless AI generated, which defaults to 'published' in migration)
        if (!$request->has('status')) {
            $data['status'] = $request->is_ai_generated ? 'published' : 'pending';
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('news', 'public');
            $data['image'] = Storage::url($path);
        }

        $news = News::create($data);

        return response()->json([
            'success' => true,
            'message' => 'News created successfully',
            'data' => $news,
        ], 201);
    }

    public function show($id)
    {
        $news = News::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'sometimes|required|string',
            'content' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'date' => 'nullable|date', // Made optional
            'category' => 'sometimes|required|string',
            'is_ai_generated' => 'boolean',
            'status' => 'nullable|in:published,pending,rejected',
            'source_url' => 'nullable|url',
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
            if ($news->image && $news->image !== '/news-placeholder.svg') {
                $oldPath = str_replace('/storage/', '', $news->image);
                Storage::disk('public')->delete($oldPath);
            }

            $image = $request->file('image');
            $path = $image->store('news', 'public');
            $data['image'] = Storage::url($path);
        }

        $news->update($data);

        return response()->json([
            'success' => true,
            'message' => 'News updated successfully',
            'data' => $news->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $news = News::findOrFail($id);

        // Delete image if exists
        if ($news->image && $news->image !== '/news-placeholder.svg') {
            $oldPath = str_replace('/storage/', '', $news->image);
            Storage::disk('public')->delete($oldPath);
        }

        $news->delete();

        return response()->json([
            'success' => true,
            'message' => 'News deleted successfully',
        ]);
    }

    /**
     * Generate AI news by topic
     */
    public function generateByTopic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|max:255',
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $count = $request->get('count', 1);
            $generatedNews = [];

            for ($i = 0; $i < $count; $i++) {
                $article = $this->newsGenerationService->generateArticleForTopic($request->topic);
                if ($article && isset($article['success']) && $article['success']) {
                    $generatedNews[] = $article['article'];
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($generatedNews) . ' news article(s) generated successfully',
                'data' => $generatedNews,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate news',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run news generation cronjob manually
     */
    public function runCronjob()
    {
        try {
            Artisan::call('app:generate-daily-news');
            
            return response()->json([
                'success' => true,
                'message' => 'News generation cronjob executed successfully',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run cronjob',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve news article
     */
    public function approve($id)
    {
        $news = News::findOrFail($id);
        $news->update(['status' => 'published']);

        return response()->json([
            'success' => true,
            'message' => 'News approved and published successfully',
            'data' => $news->fresh(),
        ]);
    }

    /**
     * Reject news article
     */
    public function reject($id)
    {
        $news = News::findOrFail($id);
        $news->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'News rejected successfully',
            'data' => $news->fresh(),
        ]);
    }
}
