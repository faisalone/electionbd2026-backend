<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsGenerationService
{
    /**
     * Hourly News Topics
     */
    private array $searchTopics = [
        'নির্বাচন',
        'ভোট',
        'রাজনীতি',
    ];

    /**
     * Max events per topic (generates multiple articles per topic)
     */
    private int $maxEventsPerTopic = 3;

    /**
     * Generate news articles for all configured topics.
     * Each topic can generate multiple articles based on different events.
     */
    public function generateDailyNews(): array
    {
        $results = [];

        foreach ($this->searchTopics as $topic) {
            try {
                // Generate multiple events per topic
                $topicResults = $this->generateMultipleEventsForTopic($topic);
                $results = array_merge($results, $topicResults);
                
                sleep(2); // Rate limiting between topics
            } catch (\Exception $e) {
                Log::error('Error generating news for topic', [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
                
                $results[] = [
                    'topic' => $topic,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Generate multiple articles for a single topic (different events/angles).
     */
    private function generateMultipleEventsForTopic(string $topic): array
    {
        Log::info('Generating multiple events for topic', ['topic' => $topic]);

        $results = [];
        
        // Fetch sources from Google API
        $sources = $this->fetchSources($topic, 15); // Get more sources for variety

        if (empty($sources)) {
            Log::warning('No sources found', ['topic' => $topic]);
            return [[
                'topic' => $topic,
                'success' => false,
                'message' => 'No recent news found',
            ]];
        }

        // Group sources by similarity to identify different events
        $eventGroups = $this->groupSourcesByEvent($sources);
        
        Log::info('Identified events', [
            'topic' => $topic,
            'event_count' => count($eventGroups),
            'sources_total' => count($sources),
        ]);

        // Generate article for each event group
        $eventsGenerated = 0;
        foreach ($eventGroups as $index => $eventSources) {
            if ($eventsGenerated >= $this->maxEventsPerTopic) {
                break;
            }

            $eventLabel = "{$topic} (ঘটনা " . ($index + 1) . ")";
            
            // Check for duplicates
            $articleTitles = array_column($eventSources, 'title');
            if ($this->isDuplicateInDatabase($articleTitles)) {
                Log::info('Duplicate event detected, skipping', ['event' => $eventLabel]);
                $results[] = [
                    'topic' => $eventLabel,
                    'success' => false,
                    'message' => 'Similar news already exists',
                    'duplicate' => true,
                ];
                continue;
            }

            // Generate article for this event
            $article = $this->generateArticle($eventSources, $topic);

            if (!$article) {
                $results[] = [
                    'topic' => $eventLabel,
                    'success' => false,
                    'message' => 'Failed to generate article',
                ];
                continue;
            }

            // Publish article
            $published = $this->publishArticle($article);

            $results[] = [
                'topic' => $eventLabel,
                'success' => true,
                'sources_count' => count($eventSources),
                'published' => $published['success'],
                'article_uid' => $published['uid'] ?? null,
                'message' => $published['message'],
            ];

            if ($published['success']) {
                $eventsGenerated++;
            }

            sleep(1); // Small delay between events
        }

        return $results;
    }

    /**
     * Generate a single article for a given topic.
     */
    public function generateArticleForTopic(string $topic): array
    {
        Log::info('Starting article generation', ['topic' => $topic]);

        // Fetch sources from Google API
        $sources = $this->fetchSources($topic);

        if (empty($sources)) {
            Log::warning('No sources found', ['topic' => $topic]);
            return [
                'topic' => $topic,
                'success' => false,
                'message' => 'No recent news found',
            ];
        }

        // Check for duplicates in database
        $articleTitles = array_column($sources, 'title');
        
        if ($this->isDuplicateInDatabase($articleTitles)) {
            Log::info('Duplicate news detected, skipping', ['topic' => $topic]);
            return [
                'topic' => $topic,
                'success' => false,
                'message' => 'Similar news already exists',
                'duplicate' => true,
            ];
        }

        // Generate article using AI
        $article = $this->generateArticle($sources, $topic);

        if (!$article) {
            return [
                'topic' => $topic,
                'success' => false,
                'message' => 'Failed to generate article',
            ];
        }

        // Publish article
        $published = $this->publishArticle($article);

        return [
            'topic' => $topic,
            'success' => true,
            'sources_count' => count($sources),
            'published' => $published['success'],
            'article_uid' => $published['uid'] ?? null,
            'message' => $published['message'],
        ];
    }

    /**
     * Fetch news sources from Google Custom Search API.
     * Uses LAST 1 HOUR filter (same as user's Google News search)
     */
    private function fetchSources(string $query, int $maxResults = 10): array
    {
        $apiKey = config('services.google.api_key');
        $searchEngineId = config('services.google.search_engine_id');

        if (!$apiKey || !$searchEngineId) {
            Log::warning('Google Custom Search API not configured');
            return [];
        }

        try {
            // Focus query on Bangladesh political news only
            $enhancedQuery = "বাংলাদেশ {$query}";
            
            $response = Http::timeout(30)->get('https://www.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $searchEngineId,
                'q' => $enhancedQuery,
                'num' => min($maxResults, 10),
                'lr' => 'lang_bn', // Only Bengali language
                'dateRestrict' => 'd1', // Last 24 hours
                'sort' => 'date:d:s', // Sort by date, descending (newest first)
                'fileType' => '', // No specific file type
                'safe' => 'off', // Include all results
            ]);

            if (!$response->successful()) {
                Log::error('Google API error', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                return [];
            }

            $data = $response->json();
            $items = $data['items'] ?? [];

            return collect($items)
                ->filter(function ($item) {
                    $link = strtolower($item['link'] ?? '');
                    $title = strtolower($item['title'] ?? '');
                    $snippet = strtolower($item['snippet'] ?? '');
                    $source = strtolower($item['displayLink'] ?? '');
                    
                    // STRICT: Only accept these Bangladesh news domains
                    $bdNewsDomains = [
                        'prothomalo.com', 'bdnews24.com', 'thedailystar.net', 'dhakatribune.com',
                        'banglanews24.com', 'jagonews24.com', 'samakal.com', 'kalerkantho.com',
                        'ittefaq.com.bd', 'jugantor.com', 'newagebd.net', 'tbsnews.net',
                        'risingbd.com', 'barta24.com', 'bd-pratidin.com', 'banglatribune.com',
                        'mzamin.com', 'manabzamin.com', 'ntvbd.com', 'channeli.tv',
                        'somoynews.tv', 'jamuna.tv', 'bonikbarta.net', 'deshrupantor.com'
                    ];
                    
                    $isValidNewsSite = false;
                    foreach ($bdNewsDomains as $domain) {
                        if (str_contains($source, $domain) || str_contains($link, $domain)) {
                            $isValidNewsSite = true;
                            break;
                        }
                    }
                    
                    if (!$isValidNewsSite) {
                        Log::info('Filtered non-BD news site', ['source' => $source]);
                        return false;
                    }
                    
                    // Must contain Bangladesh-related keywords
                    $bdKeywords = ['বাংলাদেশ', 'ঢাকা', 'dhaka', 'bangladesh', 'চট্টগ্রাম', 'বাংলা'];
                    $hasBdKeyword = false;
                    foreach ($bdKeywords as $keyword) {
                        if (str_contains($title, $keyword) || str_contains($snippet, $keyword)) {
                            $hasBdKeyword = true;
                            break;
                        }
                    }
                    
                    if (!$hasBdKeyword) {
                        Log::info('Filtered non-BD topic', ['title' => $item['title'] ?? '']);
                        return false;
                    }
                    
                    // Exclude BBC and other international news about elections
                    $excludeKeywords = ['bbc', 'বিবিসি', 'cnn', 'reuters', 'uk election', 'us election', 'america'];
                    foreach ($excludeKeywords as $exclude) {
                        if (str_contains($title, $exclude) || str_contains($snippet, $exclude)) {
                            Log::info('Filtered international news', ['title' => $item['title'] ?? '']);
                            return false;
                        }
                    }
                    
                    // Exclude non-political topics (crime, banking, accidents, sports, entertainment)
                    $excludeTopics = [
                        'ব্যাংক', 'bank', 'নিখোঁজ', 'missing', 'গুম', 'হত্যা', 'murder', 'দুর্ঘটনা', 'accident',
                        'ক্রিকেট', 'cricket', 'ফুটবল', 'football', 'খেলা', 'sports', 'চলচ্চিত্র', 'cinema',
                        'অগ্নিকাণ্ড', 'fire', 'বন্যা', 'flood', 'ভূমিকম্প', 'earthquake', 'আবহাওয়া', 'weather',
                        'শেয়ার বাজার', 'stock market', 'ডলার', 'dollar', 'টাকা', 'taka rate', 'ব্যবসা', 'business',
                        'উপপরিচালক', 'deputy director', 'কর্মকর্তা নিখোঁজ', 'official missing'
                    ];
                    foreach ($excludeTopics as $topic) {
                        if (str_contains($title, $topic) || str_contains($snippet, $topic)) {
                            Log::info('Filtered non-political topic', ['title' => $item['title'] ?? '', 'topic' => $topic]);
                            return false;
                        }
                    }
                    
                    // Exclude shopping/book sites
                    $excludeSites = ['rokomari', 'amazon', 'daraz', 'alibaba', 'bookshop', 'ecs.gov.bd'];
                    foreach ($excludeSites as $site) {
                        if (str_contains($link, $site) || str_contains($source, $site)) {
                            Log::info('Filtered shopping site', ['source' => $source]);
                            return false;
                        }
                    }
                    
                    // Exclude book content
                    if (str_contains($title, 'বই') && str_contains($title, 'রকমারি')) {
                        Log::info('Filtered book content', ['title' => $item['title'] ?? '']);
                        return false;
                    }
                    
                    // Must have substantial content (minimum 100 characters - more realistic)
                    $snippetLength = mb_strlen($item['snippet'] ?? '', 'UTF-8');
                    if ($snippetLength < 100) {
                        Log::info('Filtered short content', ['length' => $snippetLength, 'title' => $item['title'] ?? '']);
                        return false;
                    }
                    
                    // Must contain political/election keywords
                    $politicalKeywords = [
                        'নির্বাচন', 'ভোট', 'রাজনীতি', 'দল', 'নেতা', 'সরকার', 'বিরোধী', 'প্রচারণা',
                        'প্রার্থী', 'সংসদ', 'মন্ত্রী', 'আওয়ামী লীগ', 'বিএনপি', 'জামায়াত', 'জাপা',
                        'election', 'vote', 'politics', 'political', 'party', 'campaign', 'candidate',
                        'parliament', 'minister', 'awami league', 'bnp'
                    ];
                    
                    $hasPoliticalKeyword = false;
                    foreach ($politicalKeywords as $keyword) {
                        if (str_contains($title, $keyword) || str_contains($snippet, $keyword)) {
                            $hasPoliticalKeyword = true;
                            break;
                        }
                    }
                    
                    if (!$hasPoliticalKeyword) {
                        Log::info('Filtered non-political content', ['title' => $item['title'] ?? '']);
                        return false;
                    }
                    
                    return true;
                })
                ->map(function ($item) {
                    return [
                        'title' => $item['title'] ?? '',
                        'link' => $item['link'] ?? '',
                        'snippet' => $item['snippet'] ?? '',
                        'source' => $item['displayLink'] ?? '',
                    ];
                })
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('Error fetching sources', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate article content using AI.
     */
    private function generateArticle(array $sources, string $topic): ?array
    {
        $provider = config('services.ai.provider', 'openai');

        if ($provider === 'gemini') {
            return $this->generateWithGemini($sources, $topic);
        } else {
            return $this->generateWithOpenAI($sources, $topic);
        }
    }

    /**
     * Generate article using OpenAI API.
     */
    private function generateWithOpenAI(array $sources, string $topic): ?array
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            Log::warning('OpenAI API not configured');
            return null;
        }

        $category = $this->determineCategory($topic);
        $sourceContext = $this->buildSourceContext($sources);
        $prompt = $this->buildPrompt($topic, $sourceContext);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4-turbo-preview'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional Bangladeshi news reporter. Always write in Bengali.',
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
                    'topic' => $topic,
                ]);
                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return null;
            }

            $article = json_decode($content, true);

            if (!$article || !isset($article['title'], $article['summary'], $article['content'])) {
                Log::error('Invalid article format from OpenAI', ['content' => $content]);
                return null;
            }

            // Validate article length (must have substantial content - at least 500 chars)
            $contentLength = mb_strlen($article['content'], 'UTF-8');
            if ($contentLength < 500) {
                Log::warning('Article too short, rejecting', [
                    'topic' => $topic,
                    'length' => $contentLength,
                    'title' => $article['title'],
                ]);
                return null;
            }

            $article['category'] = $category;
            $article['date'] = $this->getBengaliDate();
            $article['sources'] = $sources;

            return $article;

        } catch (\Exception $e) {
            Log::error('Error generating article with OpenAI', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate article using Gemini API.
     */
    private function generateWithGemini(array $sources, string $topic): ?array
    {
        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            Log::warning('Gemini API not configured');
            return null;
        }

        $category = $this->determineCategory($topic);
        $sourceContext = $this->buildSourceContext($sources);
        $prompt = $this->buildPrompt($topic, $sourceContext);

        try {
            $model = config('services.gemini.model', 'gemini-2.5-flash');
            
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey,
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => "You are a professional Bangladeshi news reporter. Always write in Bengali. Always respond with valid JSON only.\n\n" . $prompt
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
                    'topic' => $topic,
                ]);
                return null;
            }

            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$content) {
                return null;
            }

            // Clean markdown code blocks
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);

            $article = json_decode($content, true);

            if (!$article || !isset($article['title'], $article['summary'], $article['content'])) {
                Log::error('Invalid article format from Gemini', ['content' => $content]);
                return null;
            }

            // Validate article length (must have substantial content - at least 500 chars)
            $contentLength = mb_strlen($article['content'], 'UTF-8');
            if ($contentLength < 500) {
                Log::warning('Article too short, rejecting', [
                    'topic' => $topic,
                    'length' => $contentLength,
                    'title' => $article['title'],
                ]);
                return null;
            }

            $article['category'] = $category;
            $article['date'] = $this->getBengaliDate();
            $article['sources'] = $sources;

            return $article;

        } catch (\Exception $e) {
            Log::error('Error generating article with Gemini', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build source context from Google API results.
     */
    private function buildSourceContext(array $sources): string
    {
        return collect($sources)->take(5)->map(function ($source, $index) {
            $num = $index + 1;
            return sprintf(
                "=== সূত্র %d ===\nশিরোনাম: %s\nURL: %s\nসারাংশ: %s",
                $num,
                $source['title'] ?? '',
                $source['link'] ?? '',
                $source['snippet'] ?? ''
            );
        })->implode("\n\n---\n\n");
    }

    /**
     * Build the article generation prompt.
     */
    private function buildPrompt(string $topic, string $sourceContext): string
    {
        return <<<PROMPT
আপনি প্রথম আলো/bdnews24 এর একজন সিনিয়র রিপোর্টার। নিচের আজকের (TODAY'S) তাজা খবর থেকে একটি সংবাদ প্রতিবেদন লিখুন।

**� CRITICAL - সবচেয়ে গুরুত্বপূর্ণ নিয়ম:**
1. **কোনো তারিখ লিখবেন না** - আমরা স্বয়ংক্রিয়ভাবে আজকের তারিখ যোগ করব
2. **শুধু রাজনীতি/নির্বাচন/ভোট** - অন্য বিষয় (ব্যাংক, নিখোঁজ, ক্রীড়া) একেবারেই না
3. **কোনো placeholder নয়** - "(তারিখ)", "(নাম)", ইত্যাদি কখনো লিখবেন না
4. **একটি মাত্র বিষয়** - একাধিক অসম্পর্কিত বিষয় মেশাবেন না

**📰 সংবাদ লিখুন, প্রবন্ধ/বিশ্লেষণ নয়:**
- প্রথম প্যারায় মূল ঘটনা (Who, What, Where)
- দ্বিতীয় প্যারায় বিস্তারিত তথ্য
- তৃতীয় প্যারায় প্রতিক্রিয়া (যদি থাকে)
- ৩-৪ প্যারা, প্রতিটি ২-৩ লাইন
- কোনো section heading নয় ("**বিশ্লেষণ**", "**প্রভাব**" ইত্যাদি লিখবেন না)

**✅ অবশ্যই:**
- শুধু সূত্রে থাকা বাস্তব তথ্য ব্যবহার করুন
- "জানা গেছে", "সূত্র জানায়" - এই ধরনের reporter টোন
- নাম, স্থান, ঘটনা স্পষ্টভাবে উল্লেখ করুন
- রিপোর্টিং স্টাইল রাখুন

**❌ কখনো করবেন না:**
- পুরানো তারিখ লিখবেন না (২০১৯, ২০২০ ইত্যাদি)
- অনুমান/কল্পনা করবেন না
- সাধারণ বাক্য ("নতুন মাত্রা যোগ করবে" ইত্যাদি)
- একাধিক বিষয় মেশাবেন না

সূত্র (আজকের তাজা খবর):
{$sourceContext}

JSON:
{
  "title": "শিরোনাম (৮-১২ শব্দ)",
  "summary": "সারসংক্ষেপ (৪০-৬০ শব্দ)",
  "content": "বিস্তারিত (৩-৪ প্যারা, **bold** নাম/স্থানের জন্য)"
}

মনে রাখুন: কোনো তারিখ নয়, শুধু রাজনীতি/নির্বাচন, কোনো placeholder নয়
PROMPT;
    }

    /**
     * Publish article with duplicate detection.
     */
    private function publishArticle(array $article): array
    {
        try {
            // Check for duplicates
            $duplicate = $this->findDuplicate($article['title']);

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => 'Duplicate article detected',
                    'existing_uid' => $duplicate->uid,
                ];
            }

            // Create news article
            $news = News::create([
                'title' => $article['title'],
                'summary' => $article['summary'],
                'content' => $article['content'],
                'image' => null, // Can be added later
                'date' => $article['date'],
                'category' => $article['category'],
                'is_ai_generated' => true,
                'source_url' => isset($article['sources']) ? json_encode($article['sources']) : null,
            ]);

            return [
                'success' => true,
                'message' => 'Article published successfully',
                'uid' => $news->uid,
                'id' => $news->id,
            ];

        } catch (\Exception $e) {
            Log::error('Error publishing article', [
                'error' => $e->getMessage(),
                'title' => $article['title'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Find duplicate articles based on title similarity.
     */
    private function findDuplicate(string $title): ?News
    {
        // Exact match
        $exact = News::where('title', $title)->first();
        if ($exact) {
            return $exact;
        }

        // Similarity check
        $titleWords = $this->extractKeywords($title);
        
        if (count($titleWords) < 3) {
            return null;
        }

        $recentArticles = News::where('is_ai_generated', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        foreach ($recentArticles as $article) {
            $articleWords = $this->extractKeywords($article->title);
            $commonWords = array_intersect($titleWords, $articleWords);
            
            $similarity = count($commonWords) / max(count($titleWords), count($articleWords));
            
            if ($similarity > 0.6) {
                return $article;
            }
        }

        return null;
    }

    /**
     * Extract keywords from title.
     */
    private function extractKeywords(string $title): array
    {
        $stopWords = ['এবং', 'বা', 'কিন্তু', 'তবে', 'যে', 'যা', 'এই', 'সেই', 'ও', 'না'];
        $words = preg_split('/\s+/u', mb_strtolower($title), -1, PREG_SPLIT_NO_EMPTY);
        
        return array_values(array_diff($words, $stopWords));
    }

    /**
     * ✅ Check if similar news already exists in database (TODAY only - fast search)
     * Searches only today's articles to avoid duplicates
     */
    private function isDuplicateInDatabase(array $sourceTitles): bool
    {
        // Get today's articles only (fast query)
        $today = now()->format('Y-m-d');
        $todayArticles = News::whereDate('created_at', $today)
            ->where('is_ai_generated', true)
            ->get(['title']);

        if ($todayArticles->isEmpty()) {
            return false; // No articles today, safe to generate
        }

        // Check each source title against today's articles
        foreach ($sourceTitles as $sourceTitle) {
            $sourceKeywords = $this->extractKeywords($sourceTitle);
            
            foreach ($todayArticles as $existing) {
                $existingKeywords = $this->extractKeywords($existing->title);
                
                // Calculate similarity
                $common = count(array_intersect($sourceKeywords, $existingKeywords));
                $total = count(array_unique(array_merge($sourceKeywords, $existingKeywords)));
                
                if ($total > 0) {
                    $similarity = ($common / $total) * 100;
                    
                    // If 50%+ similarity, consider it duplicate
                    if ($similarity >= 50) {
                        Log::info('Duplicate detected', [
                            'source_title' => $sourceTitle,
                            'existing_title' => $existing->title,
                            'similarity' => round($similarity, 2) . '%',
                        ]);
                        return true;
                    }
                }
            }
        }

        return false; // No duplicates found
    }

    /**
     * Determine category based on topic.
     */
    private function determineCategory(string $topic): string
    {
        $topic = mb_strtolower($topic);

        if (str_contains($topic, '২০২৬') || str_contains($topic, '2026')) {
            return 'নির্বাচন ২০২৬';
        }

        if (str_contains($topic, 'রাজনীতি') || str_contains($topic, 'politics')) {
            return 'রাজনীতি';
        }

        return 'নির্বাচন';
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

    /**
     * Group sources by event/topic similarity.
     * Identifies different events within the same topic.
     */
    private function groupSourcesByEvent(array $sources): array
    {
        if (empty($sources)) {
            return [];
        }

        $groups = [];
        $grouped = [];

        foreach ($sources as $index => $source) {
            if (isset($grouped[$index])) {
                continue; // Already grouped
            }

            // Start a new group with this source
            $currentGroup = [$source];
            $grouped[$index] = true;

            // Find similar sources
            $sourceKeywords = $this->extractKeywords($source['title']);

            foreach ($sources as $compareIndex => $compareSource) {
                if ($compareIndex === $index || isset($grouped[$compareIndex])) {
                    continue;
                }

                $compareKeywords = $this->extractKeywords($compareSource['title']);
                $common = count(array_intersect($sourceKeywords, $compareKeywords));
                $total = count(array_unique(array_merge($sourceKeywords, $compareKeywords)));

                // If 40%+ similarity, group together (same event)
                if ($total > 0 && ($common / $total) >= 0.4) {
                    $currentGroup[] = $compareSource;
                    $grouped[$compareIndex] = true;
                }

                // Max 5 sources per event
                if (count($currentGroup) >= 5) {
                    break;
                }
            }

            $groups[] = $currentGroup;
        }

        // Sort groups by size (largest first)
        usort($groups, function($a, $b) {
            return count($b) - count($a);
        });

        return $groups;
    }
}
