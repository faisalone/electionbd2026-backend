<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AINewsService
{
    protected $openaiKey;
    protected $geminiKey;
    protected $useGemini;

    public function __construct()
    {
        $this->openaiKey = config('services.openai.api_key');
        $this->geminiKey = config('services.gemini.api_key');
        $this->useGemini = config('services.ai.provider', 'openai') === 'gemini';
    }

    /**
     * Generate election news using AI
     */
    public function generateElectionNews(int $count = 1): array
    {
        $generatedNews = [];

        for ($i = 0; $i < $count; $i++) {
            try {
                $newsData = $this->useGemini 
                    ? $this->generateWithGemini()
                    : $this->generateWithOpenAI();

                if ($newsData) {
                    $news = News::create([
                        'title' => $newsData['title'],
                        'summary' => $newsData['summary'],
                        'content' => $newsData['content'],
                        'image' => $newsData['image'] ?? $this->getDefaultImage(),
                        'date' => $this->getBengaliDate(),
                        'category' => $newsData['category'] ?? 'নির্বাচন',
                        'is_ai_generated' => true,
                        'source_url' => null,
                    ]);

                    $generatedNews[] = $news;
                }
            } catch (\Exception $e) {
                Log::error('AI News generation failed', ['error' => $e->getMessage()]);
            }
        }

        return $generatedNews;
    }

    /**
     * Generate news with OpenAI
     */
    private function generateWithOpenAI(): ?array
    {
        if (!$this->openaiKey) {
            Log::error('OpenAI API key not configured');
            return null;
        }

        try {
            $prompt = $this->getNewsPrompt();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-turbo-preview',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional Bengali news writer specializing in Bangladesh election coverage. Generate realistic, informative news articles in Bengali.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1500,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                return $this->parseNewsContent($content);
            }

            Log::error('OpenAI API request failed', ['response' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate news with Google Gemini
     */
    private function generateWithGemini(): ?array
    {
        if (!$this->geminiKey) {
            Log::error('Gemini API key not configured');
            return null;
        }

        try {
            $prompt = $this->getNewsPrompt();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->geminiKey, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "You are a professional Bengali news writer specializing in Bangladesh election coverage. Generate realistic, informative news articles in Bengali.\n\n" . $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1500,
                ]
            ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                return $this->parseNewsContent($content);
            }

            Log::error('Gemini API request failed', ['response' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Gemini exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get news generation prompt
     */
    private function getNewsPrompt(): string
    {
        $topics = [
            'নির্বাচন কমিশনের নতুন নির্দেশনা',
            'রাজনৈতিক দলের নির্বাচনী প্রস্তুতি',
            'ভোটার তালিকা হালনাগাদকরণ',
            'নির্বাচনী প্রচারণা কার্যক্রম',
            'প্রার্থীদের মনোনয়ন প্রক্রিয়া',
            'নির্বাচনী এলাকা পরিদর্শন',
            'ডিজিটাল ভোটিং সিস্টেম',
            'নির্বাচনী সহিংসতা প্রতিরোধ',
        ];

        $categories = ['নির্বাচন', 'রাজনীতি', 'প্রচারণা', 'প্রযুক্তি'];

        $randomTopic = $topics[array_rand($topics)];
        $randomCategory = $categories[array_rand($categories)];

        return "Generate a Bengali news article about '{$randomTopic}' for Bangladesh Election 2026. 

The response must be in JSON format with the following structure:
{
    \"title\": \"News title in Bengali (max 100 characters)\",
    \"summary\": \"Brief summary in Bengali (max 200 characters)\",
    \"content\": \"Full article content in Bengali (500-800 words)\",
    \"category\": \"{$randomCategory}\"
}

Make it realistic, informative, and relevant to Bangladesh's current political situation. Do not include any markdown formatting, just plain JSON.";
    }

    /**
     * Parse AI-generated content
     */
    private function parseNewsContent(string $content): ?array
    {
        try {
            // Try to extract JSON from the response
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $jsonData = json_decode($matches[0], true);
                
                if ($jsonData && isset($jsonData['title'], $jsonData['summary'], $jsonData['content'])) {
                    return $jsonData;
                }
            }

            // Fallback parsing if JSON not found
            Log::warning('Could not parse JSON from AI response', ['content' => substr($content, 0, 200)]);
            return null;
        } catch (\Exception $e) {
            Log::error('News content parsing failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get default news image
     */
    private function getDefaultImage(): string
    {
        $images = [
            'https://images.unsplash.com/photo-1540910419892-4a36d2c3266c?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1529699211952-734e80c4d42b?w=800&h=600&fit=crop',
        ];

        return $images[array_rand($images)];
    }

    /**
     * Get current date in Bengali
     */
    private function getBengaliDate(): string
    {
        $bengaliMonths = [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
            5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
            9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর'
        ];

        $day = $this->toBengaliNumber(date('d'));
        $month = $bengaliMonths[(int)date('m')];
        $year = $this->toBengaliNumber(date('Y'));

        return "{$day} {$month} {$year}";
    }

    /**
     * Convert English numbers to Bengali
     */
    private function toBengaliNumber($number): string
    {
        $bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($englishDigits, $bengaliDigits, (string)$number);
    }
}
