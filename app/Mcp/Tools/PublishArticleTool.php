<?php

namespace App\Mcp\Tools;

use App\Models\News;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class PublishArticleTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Publishes a generated Bangla news article to the database with duplicate detection.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'article' => $schema->string()
                ->description('JSON string of the article object with title, summary, content, category, date fields')
                ->required(),
            'image_url' => $schema->string()
                ->description('Optional image URL for the article')
                ->default(null),
            'source_urls' => $schema->string()
                ->description('JSON array of source URLs used for the article')
                ->default(null),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'article' => 'required|string',
            'image_url' => 'nullable|url|max:500',
            'source_urls' => 'nullable|string',
        ]);

        try {
            $articleData = json_decode($validated['article'], true);
            
            if (!is_array($articleData)) {
                return Response::error('Invalid article format. Must be a JSON object.');
            }

            // Validate required fields
            if (!isset($articleData['title'], $articleData['summary'], $articleData['content'])) {
                return Response::error('Article must contain title, summary, and content fields.');
            }

            $title = $articleData['title'];
            $summary = $articleData['summary'];
            $content = $articleData['content'];
            $category = $articleData['category'] ?? 'নির্বাচন';
            $date = $articleData['date'] ?? $this->getBengaliDate();
            $imageUrl = $validated['image_url'] ?? null;
            $sourceUrls = $validated['source_urls'] ? json_decode($validated['source_urls'], true) : null;

            // Check for duplicates using title similarity
            $existingArticle = $this->findDuplicate($title);

            if ($existingArticle) {
                Log::info('Duplicate article detected, skipping', [
                    'new_title' => $title,
                    'existing_title' => $existingArticle->title,
                    'existing_id' => $existingArticle->id,
                ]);

                return Response::text(json_encode([
                    'success' => false,
                    'message' => 'Duplicate article detected. A similar article already exists.',
                    'existing_article' => [
                        'id' => $existingArticle->id,
                        'uid' => $existingArticle->uid,
                        'title' => $existingArticle->title,
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            // Create the news article
            $news = News::create([
                'title' => $title,
                'summary' => $summary,
                'content' => $content,
                'image' => $imageUrl,
                'date' => $date,
                'category' => $category,
                'is_ai_generated' => true,
                'source_url' => $sourceUrls ? json_encode($sourceUrls) : null,
            ]);

            Log::info('Published new AI-generated article', [
                'id' => $news->id,
                'uid' => $news->uid,
                'title' => $news->title,
                'category' => $news->category,
            ]);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Article published successfully',
                'article' => [
                    'id' => $news->id,
                    'uid' => $news->uid,
                    'title' => $news->title,
                    'summary' => $news->summary,
                    'category' => $news->category,
                    'date' => $news->date,
                    'is_ai_generated' => $news->is_ai_generated,
                    'created_at' => $news->created_at->toDateTimeString(),
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        } catch (\Exception $e) {
            Log::error('Error publishing article', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error('An error occurred while publishing the article: ' . $e->getMessage());
        }
    }

    /**
     * Find duplicate articles based on title similarity.
     */
    private function findDuplicate(string $title): ?News
    {
        // Check for exact match
        $exact = News::where('title', $title)->first();
        if ($exact) {
            return $exact;
        }

        // Check for similar titles (basic similarity check)
        $titleWords = $this->extractKeywords($title);
        
        if (count($titleWords) < 3) {
            return null; // Too short to check similarity
        }

        // Find articles with similar keywords
        $recentArticles = News::where('is_ai_generated', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        foreach ($recentArticles as $article) {
            $articleWords = $this->extractKeywords($article->title);
            $commonWords = array_intersect($titleWords, $articleWords);
            
            // If more than 60% of keywords match, consider it duplicate
            $similarity = count($commonWords) / max(count($titleWords), count($articleWords));
            
            if ($similarity > 0.6) {
                return $article;
            }
        }

        return null;
    }

    /**
     * Extract keywords from title for similarity comparison.
     */
    private function extractKeywords(string $title): array
    {
        // Remove common Bangla stop words and split
        $stopWords = ['এবং', 'বা', 'কিন্তু', 'তবে', 'যে', 'যা', 'এই', 'সেই', 'ও', 'না'];
        
        // Split by spaces and filter
        $words = preg_split('/\s+/u', mb_strtolower($title), -1, PREG_SPLIT_NO_EMPTY);
        
        return array_values(array_diff($words, $stopWords));
    }

    /**
     * Get current date in Bengali format.
     */
    private function getBengaliDate(): string
    {
        $englishMonths = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $bengaliMonths = [
            'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
            'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'
        ];

        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bengaliNumbers = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        $date = now()->format('d F Y');
        $date = str_replace($englishMonths, $bengaliMonths, $date);
        $date = str_replace($englishNumbers, $bengaliNumbers, $date);

        return $date;
    }
}
