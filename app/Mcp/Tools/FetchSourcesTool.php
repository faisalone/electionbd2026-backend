<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class FetchSourcesTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Fetches news sources from Google Custom Search API related to Bangladesh elections and politics.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The search query (e.g., "নির্বাচন", "Bangladesh election 2026")')
                ->required(),
            'max_results' => $schema->integer()
                ->description('Maximum number of results to fetch (default: 10)')
                ->default(10),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'query' => 'required|string|max:200',
            'max_results' => 'integer|min:1|max:20',
        ]);

        $query = $validated['query'];
        $maxResults = $validated['max_results'] ?? 10;

        try {
            $apiKey = config('services.google.api_key');
            $searchEngineId = config('services.google.search_engine_id');

            if (!$apiKey || !$searchEngineId) {
                return Response::error('Google Custom Search API credentials not configured. Please set GOOGLE_API_KEY and GOOGLE_SEARCH_ENGINE_ID in your .env file.');
            }

            // Fetch from Google Custom Search API  
            // Note: API doesn't support 'h1' (hours). Using 'd1' (24h) + filter after scraping
            $response = Http::get('https://www.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $searchEngineId,
                'q' => $query,
                'num' => min($maxResults, 10),
                'lr' => 'lang_bn|lang_en',
                'dateRestrict' => 'd1', // Last 24 hours (API doesn't support 'h1')
                'sort' => 'date:d:s', // Sort by date descending (newest first)
            ]);

            if (!$response->successful()) {
                Log::error('Google Custom Search API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return Response::error('Failed to fetch news sources from Google Custom Search API.');
            }

            $data = $response->json();
            $items = $data['items'] ?? [];

            // Filter OUT Wikipedia, social media, and blocked sites
            $blockedDomains = [
                'wikipedia.org',
                'wikimedia.org',
                'facebook.com',
                'twitter.com',
                'youtube.com',
                'instagram.com',
                'reddit.com',
            ];

            $sources = collect($items)
                ->filter(function ($item) use ($blockedDomains) {
                    $link = $item['link'] ?? '';
                    
                    // ✅ Block Wikipedia and other unreliable sources
                    foreach ($blockedDomains as $blocked) {
                        if (str_contains(strtolower($link), $blocked)) {
                            Log::info('Blocked source filtered out', [
                                'url' => $link,
                                'reason' => "Contains blocked domain: {$blocked}"
                            ]);
                            return false;
                        }
                    }
                    
                    return true;
                })
                ->map(function ($item) {
                    return [
                        'title' => $item['title'] ?? '',
                        'link' => $item['link'] ?? '',
                        'snippet' => $item['snippet'] ?? '',
                        'source' => $item['displayLink'] ?? '',
                        'pagemap' => $item['pagemap'] ?? null, // May contain publish date
                    ];
                })
                ->values()
                ->toArray();

            if (empty($sources)) {
                Log::warning('No valid sources after filtering', ['query' => $query]);
                return Response::text(json_encode([
                    'success' => false,
                    'message' => 'No valid news sources found after filtering (Wikipedia and social media excluded)',
                    'query' => $query,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            Log::info('Fetched and filtered news sources', [
                'query' => $query,
                'total_results' => count($items),
                'filtered_count' => count($sources),
            ]);

            return Response::text(json_encode([
                'success' => true,
                'query' => $query,
                'count' => count($sources),
                'sources' => $sources,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        } catch (\Exception $e) {
            Log::error('Error fetching news sources', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return Response::error('An error occurred while fetching news sources: ' . $e->getMessage());
        }
    }
}
