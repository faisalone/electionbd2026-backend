<?php

namespace App\Mcp\Tools;

use App\Services\NewsSourceService;
use Illuminate\JsonSchema\JsonSchema;
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

    public function __construct(private NewsSourceService $newsSourceService)
    {
    }

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
            $sources = $this->newsSourceService->fetchSources($query, $maxResults);

            if (empty($sources)) {
                Log::warning('No valid sources returned', ['query' => $query]);

                return Response::text(json_encode([
                    'success' => false,
                    'message' => 'No valid Bangladesh election news found for the provided query.',
                    'query' => $query,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return Response::text(json_encode([
                'success' => true,
                'query' => $query,
                'count' => count($sources),
                'sources' => $sources,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('Error fetching news sources', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return Response::error('An unexpected error occurred while fetching news sources: ' . $e->getMessage());
        }
    }
}
