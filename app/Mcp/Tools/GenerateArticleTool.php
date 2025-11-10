<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GenerateArticleTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Generates a fresh Bangla news article using AI based on provided sources about Bangladesh elections.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sources' => $schema->string()
                ->description('JSON string of news sources array with title, link, snippet fields')
                ->required(),
            'topic' => $schema->string()
                ->description('Main topic/theme for the article')
                ->required(),
            'category' => $schema->string()
                ->enum(['রাজনীতি', 'নির্বাচন ২০২৬', 'নির্বাচন'])
                ->description('Article category')
                ->default('নির্বাচন'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'sources' => 'required|string',
            'topic' => 'required|string|max:500',
            'category' => 'string|in:রাজনীতি,নির্বাচন ২০২৬,নির্বাচন',
        ]);

        try {
            $sources = json_decode($validated['sources'], true);
            if (!is_array($sources)) {
                return Response::error('Invalid sources format. Must be a JSON array.');
            }

            $topic = $validated['topic'];
            $category = $validated['category'] ?? 'নির্বাচন';

            // Prepare source context
            $sourceContext = collect($sources)->map(function ($source) {
                return sprintf(
                    "শিরোনাম: %s\nসূত্র: %s\nবিবরণ: %s",
                    $source['title'] ?? '',
                    $source['link'] ?? '',
                    $source['snippet'] ?? ''
                );
            })->implode("\n\n");

            // Build prompt
            $prompt = <<<PROMPT
আপনি একজন পেশাদার বাংলাদেশী সংবাদ প্রতিবেদক। নিচের সূত্রগুলির ভিত্তিতে "{$topic}" বিষয়ে একটি নতুন বাংলা সংবাদ নিবন্ধ তৈরি করুন।

সূত্রসমূহ:
{$sourceContext}

নিবন্ধটিতে অবশ্যই থাকতে হবে:
1. একটি আকর্ষণীয় বাংলা শিরোনাম (সর্বোচ্চ 100 অক্ষর)
2. একটি সংক্ষিপ্ত সারাংশ (150-200 অক্ষর)
3. সম্পূর্ণ নিবন্ধ বিষয়বস্তু (500-800 শব্দ)

JSON ফরম্যাটে প্রদান করুন:
{
  "title": "বাংলা শিরোনাম",
  "summary": "সংক্ষিপ্ত সারাংশ",
  "content": "সম্পূর্ণ নিবন্ধ বিষয়বস্তু"
}

দ্রষ্টব্য:
- শুধুমাত্র বাংলায় লিখুন
- তথ্য নির্ভরযোগ্য ও সঠিক হতে হবে
- কোনো মিথ্যা বা বিভ্রান্তিকর তথ্য যুক্ত করবেন না
- নিরপেক্ষ ও পেশাদার টোন বজায় রাখুন
PROMPT;

            // Determine AI provider
            $provider = config('services.ai.provider', 'openai');

            if ($provider === 'gemini') {
                $article = $this->generateWithGemini($prompt);
            } else {
                $article = $this->generateWithOpenAI($prompt);
            }

            if (!$article) {
                return Response::error('Failed to generate article using AI API.');
            }

            if (!isset($article['title'], $article['summary'], $article['content'])) {
                return Response::error('Invalid article format received from AI.');
            }

            // Add metadata
            $article['category'] = $category;
            $article['date'] = $this->getBengaliDate();
            $article['sources'] = $sources;

            Log::info('Generated news article', [
                'topic' => $topic,
                'category' => $category,
                'title_length' => mb_strlen($article['title']),
                'content_length' => mb_strlen($article['content']),
            ]);

            return Response::text(json_encode([
                'success' => true,
                'article' => $article,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        } catch (\Exception $e) {
            Log::error('Error generating article', [
                'error' => $e->getMessage(),
                'topic' => $validated['topic'] ?? null,
            ]);

            return Response::error('An error occurred while generating the article: ' . $e->getMessage());
        }
    }

    /**
     * Generate article using OpenAI API.
     */
    private function generateWithOpenAI(string $prompt): ?array
    {
        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            Log::error('OpenAI API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4-turbo-preview'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional Bangladeshi news reporter specializing in election coverage. Always write in Bengali.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return null;
            }

            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('OpenAI generation error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate article using Gemini API.
     */
    private function generateWithGemini(string $prompt): ?array
    {
        $apiKey = config('services.gemini.api_key');
        
        if (!$apiKey) {
            Log::error('Gemini API key not configured');
            return null;
        }

        try {
            $model = config('services.gemini.model', 'gemini-2.5-flash');
            
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey,
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => "You are a professional Bangladeshi news reporter specializing in election coverage. Always write in Bengali. Always respond with valid JSON only.\n\n" . $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2000,
                        'topP' => 0.8,
                        'topK' => 40,
                    ],
                ]
            );

            if (!$response->successful()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$content) {
                return null;
            }

            // Clean up potential markdown code blocks
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);

            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('Gemini generation error', ['error' => $e->getMessage()]);
            return null;
        }
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
