<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsSourceService
{
    /**
     * Whitelisted Bangladesh news domains.
     *
     * @var array<int, string>
     */
    private array $bdNewsDomains = [
        'prothomalo.com',
        'bdnews24.com',
        'thedailystar.net',
        'dhakatribune.com',
        'banglanews24.com',
        'jagonews24.com',
        'samakal.com',
        'kalerkantho.com',
        'ittefaq.com.bd',
        'jugantor.com',
        'newagebd.net',
        'tbsnews.net',
        'risingbd.com',
        'barta24.com',
        'bd-pratidin.com',
        'banglatribune.com',
        'mzamin.com',
        'manabzamin.com',
        'ntvbd.com',
        'channeli.tv',
        'somoynews.tv',
        'jamuna.tv',
        'bonikbarta.net',
        'deshrupantor.com',
        // Additional trusted sources
        'bbc.com',
        'dw.com',
        'voabengnali.com',
        'bssnews.net',
        'dhakapost.com',
        'itvbd.com',
        'duaa-news.com',
        'ajkerpatrika.com',
        'dailybangla.com.bd',
        'dainikbangla.com.bd',
    ];

    /**
     * Fetch latest Bangladesh election/politics sources with full article content.
     */
    public function fetchSources(string $query, int $maxResults = 10): array
    {
        $apiKey = config('services.google.api_key');
        $searchEngineId = config('services.google.search_engine_id');

        if (!$apiKey || !$searchEngineId) {
            Log::warning('Google Custom Search API not configured');
            return [];
        }

        try {
            $enhancedQuery = trim(sprintf('বাংলাদেশ %s', $query));

            $response = Http::timeout(30)->get('https://www.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $searchEngineId,
                'q' => $enhancedQuery,
                'num' => min($maxResults * 2, 10),
                'lr' => 'lang_bn|lang_en',
                'dateRestrict' => 'h2', // Last 2 hours for better freshness
                'sort' => 'date:d:s',
            ]);

            if (!$response->successful()) {
                Log::error('Google Custom Search API error', [
                    'status' => $response->status(),
                    'query' => $query,
                    'body' => $response->body(),
                ]);

                return [];
            }

            $items = $response->json('items', []);
            $sources = [];

            foreach ($items as $item) {
                if (count($sources) >= $maxResults) {
                    break;
                }

                $link = $this->normalizeUrl($item['link'] ?? '');
                $displayLink = strtolower($item['displayLink'] ?? '');
                $title = $this->normalizeText($item['title'] ?? '');
                $snippet = $this->normalizeText($item['snippet'] ?? '');

                if (!$link || !$title || !$snippet) {
                    continue;
                }

                if (!$this->isValidDomain($link, $displayLink)) {
                    continue;
                }

                if (!$this->isRelevantSnippet($title, $snippet)) {
                    continue;
                }

                $articleData = $this->fetchArticleContent($link);

                if (!$articleData) {
                    continue;
                }

                $sources[] = [
                    'title' => $title,
                    'link' => $link,
                    'snippet' => $snippet,
                    'source' => $displayLink,
                    'content' => $articleData['content'],
                    'excerpt' => $articleData['excerpt'],
                    'word_count' => $articleData['word_count'],
                    'published_at' => $articleData['published_at'],
                ];
            }

            Log::info('Fetched Bangladesh news sources', [
                'query' => $query,
                'requested' => $maxResults,
                'returned' => count($sources),
            ]);

            return $sources;
        } catch (\Throwable $e) {
            Log::error('Unexpected error while fetching news sources', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Determine if the result belongs to the approved domain list.
     */
    private function isValidDomain(string $link, string $displayLink): bool
    {
        $linkHost = parse_url($link, PHP_URL_HOST);
        $linkHost = $linkHost ? strtolower($linkHost) : '';

        foreach ($this->bdNewsDomains as $domain) {
            if (str_contains($linkHost, $domain) || str_contains($displayLink, $domain)) {
                return true;
            }
        }

        Log::debug('Source filtered by domain', [
            'link' => $link,
            'displayLink' => $displayLink,
        ]);

        return false;
    }

    /**
     * Perform topic relevance checks using title/snippet signals.
     */
    private function isRelevantSnippet(string $title, string $snippet): bool
    {
        $text = mb_strtolower($title . ' ' . $snippet);

        $bdKeywords = ['বাংলাদেশ', 'ঢাকা', 'bangladesh', 'চট্টগ্রাম', 'দেশ', 'জাতীয়'];
        $politicalKeywords = [
            'নির্বাচন', 'ভোট', 'রাজনীতি', 'দল', 'প্রার্থী', 'সরকার', 'বিরোধী', 'প্রচারণা',
            'সংসদ', 'আওয়ামী লীগ', 'বিএনপি', 'জামায়াত', 'জাপা', 'campaign', 'election',
        ];

        $excludeKeywords = [
            'ক্রিকেট', 'খেলা', 'খেলাধুলা', 'accident', 'দুর্ঘটনা', 'অপরাধ', 'crime', 'bank',
            'ব্যাংক', 'মুদ্রা', 'weather', 'আবহাওয়া', 'ফুটবল', 'entertainment', 'চলচ্চিত্র',
            'পোড়া', 'অগ্নিকাণ্ড', 'ভূমিকম্প', 'sports', 'stock market', 'শেয়ার বাজার', 'গুজব',
            'miss universe', 'মিস ইউনিভার্স', 'বিউটি', 'সুন্দরী', 'pageant', 'মডেল',
            'নিখোঁজ', 'হত্যা', 'ধর্ষণ', 'বিনোদন', 'সিনেমা', 'গান', 'নাটক',
        ];

        if (!$this->containsAny($text, $bdKeywords)) {
            Log::debug('Source filtered (missing Bangladesh keyword)', ['title' => $title]);
            return false;
        }

        if (!$this->containsAny($text, $politicalKeywords)) {
            Log::debug('Source filtered (missing political keyword)', ['title' => $title]);
            return false;
        }

        if ($this->containsAny($text, $excludeKeywords)) {
            Log::debug('Source filtered (excluded keyword)', ['title' => $title]);
            return false;
        }

        return true;
    }

    /**
     * Retrieve and clean the article body from the original URL.
     */
    private function fetchArticleContent(string $url): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; ElectionBDNewsBot/1.0; +https://electionbd2026.local)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(25)->get($url);

            if (!$response->successful()) {
                Log::debug('Article fetch failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $html = $this->normalizeEncoding($response->body(), $response->header('Content-Type'));

            $content = $this->extractMainContent($html);

            if (!$content || mb_strlen($content) < 400) {
                Log::debug('Article skipped (content too short)', [
                    'url' => $url,
                    'length' => mb_strlen($content ?? ''),
                ]);

                return null;
            }

            $content = $this->normalizeText($content);
            $content = $this->limitWords($content, 1500);

            $excerpt = $this->makeExcerpt($content, 600);
            $wordCount = $this->countWords($content);
            $publishedAt = $this->extractPublishedAt($html);

            return [
                'content' => $content,
                'excerpt' => $excerpt,
                'word_count' => $wordCount,
                'published_at' => $publishedAt,
            ];
        } catch (\Throwable $e) {
            Log::debug('Article fetch error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Attempt to isolate the main readable article text from the page.
     */
    private function extractMainContent(string $html): ?string
    {
        if (!$html) {
            return null;
        }

        $internalErrors = libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);

        $this->removeNoiseNodes($dom);

        $xpath = new \DOMXPath($dom);
        $candidates = $xpath->query(
            '//article | //main | //section[contains(@class, "content") or contains(@class, "article") or contains(@id, "content") or contains(@id, "article")] | //div[contains(@class, "content") or contains(@class, "article") or contains(@class, "details")]'
        );

        $bestText = '';

        if ($candidates && $candidates->length > 0) {
            foreach ($candidates as $node) {
                $text = $this->normalizeWhitespace($node->textContent ?? '');
                if (mb_strlen($text) > mb_strlen($bestText)) {
                    $bestText = $text;
                }
            }
        }

        if (!$bestText) {
            $bodyNodes = $dom->getElementsByTagName('body');
            if ($bodyNodes->length > 0) {
                $bestText = $this->normalizeWhitespace($bodyNodes->item(0)?->textContent ?? '');
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $bestText ? trim($bestText) : null;
    }

    /**
     * Remove unwanted tags that pollute article extraction.
     */
    private function removeNoiseNodes(\DOMDocument $dom): void
    {
        $removeTags = ['script', 'style', 'noscript', 'form', 'header', 'footer', 'nav', 'aside', 'svg'];

        foreach ($removeTags as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            $length = $nodes->length;

            // Collect nodes first to avoid live list mutation issues
            $toRemove = [];
            for ($i = 0; $i < $length; $i++) {
                $toRemove[] = $nodes->item($i);
            }

            foreach ($toRemove as $node) {
                $node?->parentNode?->removeChild($node);
            }
        }
    }

    /**
     * Extract published time metadata when available.
     */
    private function extractPublishedAt(string $html): ?string
    {
        $internalErrors = libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        $candidates = $xpath->query(
            "//meta[@property='article:published_time' or @name='article:published_time' or @name='publish-date' or @name='pubdate' or @name='date']"
        );

        foreach ($candidates as $candidate) {
            $content = $candidate->getAttribute('content');
            $content = $this->normalizeText($content);
            if ($content) {
                libxml_clear_errors();
                libxml_use_internal_errors($internalErrors);
                return $content;
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return null;
    }

    /**
     * Ensure text is valid UTF-8 and whitespace-normalised.
     */
    private function normalizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = $this->forceUtf8($value);
        $value = $this->normalizeWhitespace($value);

        return $value;
    }

    private function normalizeWhitespace(string $value): string
    {
        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function normalizeEncoding(string $html, ?string $contentType): string
    {
        $encoding = null;

        if ($contentType && preg_match('/charset=([^;]+)/i', $contentType, $matches)) {
            $encoding = strtoupper(trim($matches[1]));
        }

        if (!$encoding) {
            $encoding = mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252', 'ASCII'], true) ?: 'UTF-8';
        }

        if (strtoupper($encoding) !== 'UTF-8') {
            $converted = @iconv($encoding, 'UTF-8//IGNORE', $html);
            if ($converted !== false) {
                $html = $converted;
            }
        }

        return $this->forceUtf8($html);
    }

    private function forceUtf8(string $value): string
    {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private function limitWords(string $text, int $limit): string
    {
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return $text;
        }

        if (count($words) <= $limit) {
            return $text;
        }

        $limited = array_slice($words, 0, $limit);

        return implode(' ', $limited);
    }

    private function countWords(string $text): int
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);
        return count($matches[0]);
    }

    private function makeExcerpt(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length), " \t\n\r\0\x0B") . '…';
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
}
