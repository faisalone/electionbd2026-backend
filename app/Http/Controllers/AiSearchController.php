<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Candidate;
use App\Models\Party;
use App\Models\News;
use App\Models\Poll;
use App\Models\Seat;
use App\Models\TimelineEvent;
use App\Models\SearchQuery;
use App\Models\SearchQueryView;

class AiSearchController extends Controller
{
    /**
     * AI-powered search using Gemini with reasoning
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $query = $request->input('query');
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        
        // Advanced search tracking (Google-like)
        $searchQuery = SearchQuery::firstOrCreate(
            ['query' => $query],
            [
                'view_count' => 0,
                'unique_users' => 0,
                'last_searched_at' => now(),
            ]
        );

        $view = SearchQueryView::firstOrNew([
            'search_query_id' => $searchQuery->id,
            'ip_address' => $ipAddress,
        ]);

        $isNewUser = !$view->exists;

        $view->fill([
            'user_agent' => $userAgent,
            'view_count' => $view->view_count + 1,
            'first_viewed_at' => $view->first_viewed_at ?? now(),
            'last_viewed_at' => now(),
        ])->save();

        $searchQuery->incrementViews($isNewUser);

        try {
            // Phase 1: AI analyzes query and creates search strategy
            $searchPlan = $this->analyzeQueryWithAi($query);
            
            // Enhancement: Force include conduct_rules for relevant queries
            if ($this->isConductRuleQuery($query)) {
                $searchPlan['search_tables'][] = 'conduct_rules';
                $searchPlan['search_tables'] = array_unique($searchPlan['search_tables']);
                if (!in_array('নির্বাচনী আচরণবিধি', $searchPlan['topics'] ?? [])) {
                    $searchPlan['topics'][] = 'নির্বাচনী আচরণবিধি';
                }
            }
            
            // Phase 2: Execute intelligent search based on AI's understanding
            $results = $this->executeIntelligentSearch($query, $searchPlan);
            
            // Phase 3: AI generates conversational response
            // Check if Vertex AI summary is available for conduct rules
            $vertexSummary = null;
            if (isset($results['conduct_rules']) && count($results['conduct_rules']) > 0) {
                $firstRule = $results['conduct_rules']->first();
                if ($firstRule && isset($firstRule->summary) && !empty($firstRule->summary)) {
                    $vertexSummary = $firstRule->summary;
                }
            }
            
            // Use Vertex AI summary if available, otherwise generate response
            $aiResponse = $vertexSummary ?? $this->generateIntelligentResponse($query, $searchPlan, $results);
            
            return response()->json([
                'success' => true,
                'query' => $query,
                'thinking' => $searchPlan['thinking'] ?? null,
                'topics_identified' => $searchPlan['topics'] ?? [],
                'response' => $aiResponse,
                'data_found' => $this->summarizeResults($results),
            ]);
        } catch (\Exception $e) {
            Log::error('AI Search error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'দুঃখিত, কিছু সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।',
            ], 500);
        }
    }

    /**
     * Live autocomplete suggestions (Google-like)
     * Returns matching queries as user types
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:100',
        ]);

        $query = $request->input('query');
        $ipAddress = $request->ip();
        
        try {
            // Get user's matching searches (personalized)
            $userMatches = SearchQueryView::where('ip_address', $ipAddress)
                ->whereHas('searchQuery', function($q) use ($query) {
                    $q->where('query', 'LIKE', "%{$query}%");
                })
                ->with('searchQuery')
                ->orderByDesc('last_viewed_at')
                ->limit(3)
                ->get()
                ->pluck('searchQuery.query')
                ->filter()
                ->unique();
            
            // Get popular matching searches (global)
            $globalMatches = SearchQuery::where('query', 'LIKE', "%{$query}%")
                ->orderByDesc('view_count')
                ->limit(7)
                ->pluck('query');
            
            // Combine: user history first, then popular
            $suggestions = $userMatches->merge($globalMatches)
                ->unique()
                ->take(10)
                ->values();
            
            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            Log::error('Autocomplete error: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'suggestions' => [],
            ]);
        }
    }

    /**
     * Get search suggestions based on IP and usage
     */
    public function suggestions(Request $request)
    {
        $ipAddress = $request->ip();
        
        try {
            // Get popular searches globally (sorted by view count)
            $globalPopular = SearchQuery::where('last_searched_at', '>=', now()->subDays(30))
                ->orderByDesc('view_count')
                ->limit(10)
                ->pluck('query');
            
            // Get user's recent searches (IP-based)
            $userRecent = SearchQueryView::where('ip_address', $ipAddress)
                ->where('last_viewed_at', '>=', now()->subDays(7))
                ->with('searchQuery')
                ->orderByDesc('last_viewed_at')
                ->limit(5)
                ->get()
                ->pluck('searchQuery.query')
                ->filter(); // Remove nulls if any
            
            // Combine and deduplicate (user recent first, then popular)
            $suggestions = $userRecent->merge($globalPopular)->unique()->take(15)->values();
            
            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            Log::error('Suggestions error: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'suggestions' => [],
            ]);
        }
    }

    /**
     * Remove a specific search suggestion for the current IP
     */
    public function removeSuggestion(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        $query = $request->input('query');
        $ipAddress = $request->ip();
        
        try {
            // Find the search query
            $searchQuery = SearchQuery::where('query', $query)->first();
            
            if ($searchQuery) {
                // Delete this IP's view record
                $view = SearchQueryView::where('search_query_id', $searchQuery->id)
                    ->where('ip_address', $ipAddress)
                    ->first();
                
                if ($view) {
                    // Decrement unique_users if this was their only view
                    if ($view->view_count > 0) {
                        $searchQuery->decrement('unique_users');
                    }
                    
                    // Decrement total view_count by this IP's contribution
                    $searchQuery->decrement('view_count', $view->view_count);
                    
                    // Delete the view record
                    $view->delete();
                    
                    // If no more views exist for this query, delete the query itself
                    if ($searchQuery->view_count <= 0) {
                        $searchQuery->delete();
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Suggestion removed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Remove suggestion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove suggestion',
            ], 500);
        }
    }

    /**
     * Check if query is about code of conduct
     */
    private function isConductRuleQuery(string $query): bool
    {
        $conductKeywords = [
            // Core terms
            'আচরণবিধি', 'নিষিদ্ধ', 'অনুমোদিত', 'করা যাবে', 'করা যাবে না', 
            'নিয়ম', 'বিধি', 'শাস্তি', 'দায়িত্ব', 'নিষেধ', 'বারণ',
            'পারবে না', 'পারবে', 'করতে পারি', 'সীমাবদ্ধ', 'সীমা',
            
            // Campaigning materials  
            'পোস্টার', 'মাইক', 'লাউডস্পিকার', 'ব্যানার', 'ফেস্টুন', 'দেয়াল', 
            'লিফলেট', 'হ্যান্ডবিল', 'বিলবোর্ড', 'প্যান্ডেল', 'গেইট', 'তোরণ',
            'আলোকসজ্জা', 'ভোটার স্লিপ',
            
            // Campaigning activities
            'প্রচার', 'প্রচারণা', 'জনসভা', 'মিছিল', 'শোডাউন', 'পথসভা', 
            'সমাবেশ', 'মশাল মিছিল', 'শোভাযাত্রা',
            
            // Prohibited activities
            'ঘুষ', 'হুমকি', 'প্রলোভন', 'মিথ্যা', 'অস্ত্র', 'বিস্ফোরক',
            'ভয়ভীতি', 'জবরদস্তি', 'বলপ্রয়োগ', 'সহিংসতা', 'উস্কানিমূলক',
            
            // Sensitive topics
            'ধর্ম', 'বর্ণ', 'জাতি', 'সাম্প্রদায়িক', 'সম্প্রদায়', 'বিভেদ',
            'কুৎসা', 'মানহানিকর', 'অশ্লীল', 'চরিত্র হনন', 'ধর্মানুভূতি',
            'লিঙ্গ', 'আক্রমণাত্মক', 'তিক্ত', 'বিভ্রান্তিকর', 'ঘৃণাত্মক',
            
            // Resources and facilities
            'সরকারি সম্পদ', 'যানবাহন', 'অনুদান', 'চাঁদা', 'বরাদ্দ', 'উপটৌকন',
            'সার্কিট হাউজ', 'ডাক-বাংলো', 'রেস্ট হাউজ', 'সংবর্ধনা',
            'হেলিকপ্টার', 'ড্রোন', 'কোয়াডকপ্টার', 'আকাশযান',
            
            // Vehicles
            'বাস', 'ট্রাক', 'রিক্সা', 'অটোরিক্সা', 'মোটরসাইকেল', 'নৌযান',
            
            // Places
            'মসজিদ', 'মন্দির', 'গির্জা', 'ক্যায়াং', 'প্যাগোডা', 'উপাসনালয়',
            'ভোটকেন্দ্র', '৪০০ গজ', 'শিক্ষা প্রতিষ্ঠান',
            
            // Time limits
            'ডেসিবেল', 'দুপুর', 'রাত', '৪৮ ঘন্টা', 'শান্তি সময়', 'cooling period',
            '২৪ ঘণ্টা', '৩ সপ্তাহ',
            
            // Digital/AI
            'AI', 'কৃত্রিম বুদ্ধিমত্তা', 'ডিপফেক', 'deepfake', 'সামাজিক মাধ্যম',
            'সামাজিক যোগাযোগ', 'ফেসবুক', 'content', 'কনটেন্ট',
            
            // Governance
            'মনোনয়নপত্র', 'রিটার্নিং অফিসার', 'নির্বাচন কমিশন',
            'প্রার্থী', 'রাজনৈতিক দল', 'সমর্থক', 'কর্মী',
            
            // Animals & misc
            'জীবন্ত প্রাণী', 'দেয়াল লিখন', 'অপচনশীল', 'প্লাস্টিক', 'পলিথিন',
            'প্রতিষ্ঠান', 'প্রকল্প', 'নির্বাচনি ক্যাম্প',
            
            // English
            'code of conduct', 'election rule', 'campaign', 'prohibited', 'allowed'
        ];
        
        foreach ($conductKeywords as $keyword) {
            if (mb_stripos($query, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Phase 1: AI analyzes the query and creates a search strategy
     */
    private function analyzeQueryWithAi(string $query): array
    {
        $geminiApiKey = config('services.gemini.api_key');
        
        if (!$geminiApiKey) {
            return $this->basicAnalysis($query);
        }

        try {
            $analysisPrompt = $this->buildAnalysisPrompt($query);
            
            $response = Http::timeout(10)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiApiKey,
                [
                    'contents' => [[
                        'parts' => [['text' => $analysisPrompt]]
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 500,
                    ]
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $analysisText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                return $this->parseAnalysis($analysisText, $query);
            }
            
            return $this->basicAnalysis($query);
            
        } catch (\Exception $e) {
            Log::error('Query analysis error: ' . $e->getMessage());
            return $this->basicAnalysis($query);
        }
    }

    /**
     * Build prompt for query analysis
     */
    private function buildAnalysisPrompt(string $query): string
    {
        return <<<PROMPT
You are an AI assistant for Bangladesh Election 2026. Analyze the user's query and determine what information to search for.

Database Tables Available (use exact names in search_tables):
1. **candidates** - Candidate information (name, age, education, party affiliation)
2. **parties** - Political parties (name, symbol, history)
3. **news** - Election news and updates
4. **polls** - Public opinion polls and predictions
5. **seats** - Electoral constituencies
6. **timeline_events** - Election schedule, dates, and timeline (IMPORTANT: use "timeline_events" not "timeline")
7. **conduct_rules** - নির্বাচনী আচরণবিধি (Election Code of Conduct) - rules about what is allowed/prohibited during election

User Query (in Bengali): "{$query}"

Analyze this query and respond in JSON format:
```json
{
  "thinking": "বিষয় বিশ্লেষণ করছি... [brief user-friendly analysis in Bengali - no technical terms, no table names, just what the user is asking about]",
  "topics": ["topic1", "topic2"],
  "search_tables": ["candidates", "parties", "news", "polls", "seats", "timeline_events", "conduct_rules"],
  "search_terms": ["term1", "term2"],
  "query_type": "specific_info|general_question|comparison|list"
}
```

CRITICAL: Use EXACT table names from the list above in "search_tables" field!

IMPORTANT: If the query is about নির্বাচনী আচরণবিধি, code of conduct, what is allowed/prohibited, rules, কি করা যাবে, কি করা যাবে না, নিষিদ্ধ, অনুমোদিত, etc. - MUST include "conduct_rules" in search_tables.

IMPORTANT for "thinking" field:
- Write in Bengali (বাংলা)
- Be conversational and friendly
- NO technical terms like "table", "database", "query"
- Describe what the USER wants to know, not what YOU will search
- Keep it under 100 characters
- Examples:
  * Good: "আপনি আওয়ামী লীগ সম্পর্কে জানতে চাচ্ছেন..."
  * Good: "ঢাকা-১ আসনের প্রার্থীদের তথ্য খুঁজছি..."
  * Good: "নির্বাচনের তারিখ সম্পর্কে তথ্য দেখছি..."
  * Good: "নির্বাচনী আচরণবিধি সম্পর্কে তথ্য দেখছি..."
  * Bad: "Searching parties table for party info"
  * Bad: "Querying timeline_events table"

Examples:
- Query: "আওয়ামী লীগ" → thinking: "আপনি আওয়ামী লীগ সম্পর্কে জানতে চাচ্ছেন..."
- Query: "ঢাকা-১" → thinking: "ঢাকা-১ আসনের প্রার্থীদের তথ্য খুঁজছি..."
- Query: "নির্বাচন কবে?" → thinking: "নির্বাচনের সময়সূচী সম্পর্কে তথ্য দেখছি..."
- Query: "কি করা যাবে না?" → thinking: "নির্বাচনী আচরণবিধি সম্পর্কে তথ্য দেখছি..."

Respond ONLY with valid JSON.
PROMPT;
    }

    /**
     * Parse AI analysis response
     */
    private function parseAnalysis(string $analysisText, string $query): array
    {
        // Extract JSON from response
        if (preg_match('/\{[\s\S]*\}/', $analysisText, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) {
                return $parsed;
            }
        }
        
        return $this->basicAnalysis($query);
    }

    /**
     * Fallback basic analysis without AI
     */
    private function basicAnalysis(string $query): array
    {
        $searchTables = [];
        $topics = [];
        
        // Simple keyword matching
        if (preg_match('/(দল|পার্টি|লীগ)/u', $query)) {
            $searchTables[] = 'parties';
            $topics[] = 'রাজনৈতিক দল';
        }
        if (preg_match('/(প্রার্থী|মনোনয়ন)/u', $query)) {
            $searchTables[] = 'candidates';
            $topics[] = 'প্রার্থী';
        }
        if (preg_match('/(খবর|সংবাদ)/u', $query)) {
            $searchTables[] = 'news';
            $topics[] = 'খবর';
        }
        if (preg_match('/(আসন|ঢাকা|চট্টগ্রাম)/u', $query)) {
            $searchTables[] = 'seats';
            $searchTables[] = 'candidates';
            $topics[] = 'নির্বাচনী আসন';
        }
        if (preg_match('/(তফসিল|সময়|কবে|তারিখ)/u', $query)) {
            $searchTables[] = 'timeline_events';
            $topics[] = 'নির্বাচনী সময়সূচী';
        }
        if (preg_match('/(জরিপ|পোল|ভোট)/u', $query)) {
            $searchTables[] = 'polls';
            $topics[] = 'জনমত জরিপ';
        }
        // Check for conduct rules (আচরণবিধি) queries
        if (preg_match('/(আচরণবিধি|নিষিদ্ধ|অনুমোদিত|করা যাবে|করা যাবে না|নিয়ম|বিধি|পোস্টার|মাইক|প্রচার|ঘুষ|হুমকি)/u', $query)) {
            $searchTables[] = 'conduct_rules';
            $topics[] = 'নির্বাচনী আচরণবিধি';
        }
        
        // If no specific match, search all
        if (empty($searchTables)) {
            $searchTables = ['parties', 'candidates', 'news', 'seats', 'timeline_events'];
        }
        
        return [
            'thinking' => 'বিষয় বিশ্লেষণ করছি...',
            'topics' => $topics ?: ['সাধারণ অনুসন্ধান'],
            'search_tables' => array_unique($searchTables),
            'search_terms' => [$query],
            'query_type' => 'general_question'
        ];
    }

    /**
     * Phase 2: Execute intelligent search based on AI's plan
     */
    private function executeIntelligentSearch(string $query, array $searchPlan): array
    {
        $results = [];
        $tablesToSearch = $searchPlan['search_tables'] ?? ['parties', 'candidates', 'news', 'seats'];
        
        foreach ($tablesToSearch as $table) {
            switch ($table) {
                case 'candidates':
                    $results['candidates'] = $this->searchCandidates($query);
                    break;
                case 'parties':
                    $results['parties'] = $this->searchParties($query);
                    break;
                case 'news':
                    $results['news'] = $this->searchNews($query);
                    break;
                case 'polls':
                    $results['polls'] = $this->searchPolls($query);
                    break;
                case 'seats':
                    $results['seats'] = $this->searchSeats($query);
                    break;
                case 'timeline_events':
                    $results['timeline'] = $this->searchTimeline($query);
                    break;
                case 'conduct_rules':
                    $results['conduct_rules'] = $this->searchConductRules($query);
                    break;
            }
        }
        
        return $results;
    }

    /**
     * Search candidates intelligently
     */
    private function searchCandidates(string $query)
    {
        return Candidate::with(['party', 'seat.district'])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('name_en', 'LIKE', "%{$query}%")
                  ->orWhere('education', 'LIKE', "%{$query}%")
                  ->orWhere('experience', 'LIKE', "%{$query}%")
                  ->orWhereHas('party', fn($pq) => $pq->where('name', 'LIKE', "%{$query}%"))
                  ->orWhereHas('seat', fn($sq) => $sq->where('name', 'LIKE', "%{$query}%"));
            })
            ->limit(10)
            ->get();
    }

    /**
     * Search parties intelligently
     */
    private function searchParties(string $query)
    {
        return Party::with('symbol')
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('name_en', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();
    }

    /**
     * Search news intelligently
     */
    private function searchNews(string $query)
    {
        return News::where('title', 'LIKE', "%{$query}%")
            ->orWhere('summary', 'LIKE', "%{$query}%")
            ->orWhere('content', 'LIKE', "%{$query}%")
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Search polls intelligently
     */
    private function searchPolls(string $query)
    {
        return Poll::with('options')
            ->where('question', 'LIKE', "%{$query}%")
            ->where('status', 'active')
            ->limit(5)
            ->get();
    }

    /**
     * Search seats intelligently
     */
    private function searchSeats(string $query)
    {
        return Seat::with(['district'])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('name_en', 'LIKE', "%{$query}%")
                  ->orWhere('area', 'LIKE', "%{$query}%")
                  ->orWhereHas('district', fn($dq) => $dq->where('name', 'LIKE', "%{$query}%"));
            })
            ->limit(10)
            ->get();
    }

    /**
     * Search timeline intelligently
     */
    private function searchTimeline(string $query)
    {
        // For "কবে" (when) questions, return all timeline events
        if (preg_match('/(কবে|তারিখ|সময়|কখন)/u', $query)) {
            return TimelineEvent::orderBy('order')->get();
        }
        
        return TimelineEvent::where('title', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->orWhere('date', 'LIKE', "%{$query}%")
            ->orderBy('order')
            ->limit(5)
            ->get();
    }

    /**
     * Search conduct rules (আচরণবিধি) intelligently
     */
    private function searchConductRules(string $query)
    {
        // Use Vertex AI Agent for conduct rules
        try {
            $vertexAi = app(\App\Services\VertexAiAgentService::class);
            
            if ($vertexAi->isAvailable()) {
                $results = $vertexAi->searchConductRules($query, 10);
                
                if ($results && isset($results['documents']) && count($results['documents']) > 0) {
                    // Convert Vertex AI format to collection
                    $conductRules = collect($results['documents'])->map(function($doc) use ($results) {
                        return (object)[
                            'title' => $doc['title'] ?? 'নির্বাচনী আচরণবিধি',
                            'description' => $doc['snippet'] ?? $doc['content'] ?? '',
                            'summary' => $results['summary'] ?? null,
                            'category' => 'নিষিদ্ধ',
                            'rule_number' => '',
                            'keywords' => '',
                        ];
                    });
                    
                    Log::info('Vertex AI Agent returned conduct rules', ['count' => $conductRules->count()]);
                    return $conductRules;
                }
            }
        } catch (\Exception $e) {
            Log::error('Vertex AI search error: ' . $e->getMessage());
        }
        
        // Return empty collection if Vertex AI fails
        return collect();
    }

    /**
     * Phase 3: Generate intelligent conversational response
     */
    private function generateIntelligentResponse(string $query, array $searchPlan, array $results): string
    {
        $geminiApiKey = config('services.gemini.api_key');
        
        if (!$geminiApiKey) {
            return $this->generateBasicResponse($results);
        }

        try {
            $context = $this->prepareIntelligentContext($results);
            $responsePrompt = $this->buildResponsePrompt($query, $searchPlan, $context);
            
            $response = Http::timeout(15)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiApiKey,
                [
                    'contents' => [[
                        'parts' => [['text' => $responsePrompt]]
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topP' => 0.95,
                        'maxOutputTokens' => 1024,
                    ]
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if ($aiText) {
                    return trim($aiText);
                }
            }
            
            return $this->generateBasicResponse($results);
            
        } catch (\Exception $e) {
            Log::error('Response generation error: ' . $e->getMessage());
            return $this->generateBasicResponse($results);
        }
    }

    /**
     * Build prompt for response generation
     */
    private function buildResponsePrompt(string $query, array $searchPlan, string $context): string
    {
        $thinking = $searchPlan['thinking'] ?? 'প্রশ্ন বিশ্লেষণ করছি';
        $topics = implode(', ', $searchPlan['topics'] ?? []);
        
        return <<<PROMPT
You are an intelligent AI assistant for Bangladesh Election 2026. Answer the user's question naturally in Bengali, like a helpful chatbot.

User Question: "{$query}"

Your Analysis: {$thinking}
Topics Identified: {$topics}

Database Information Found:
{$context}

Instructions:
1. Write a natural, conversational response in Bengali (বাংলা)
2. DO NOT start with greetings like "নমস্কার", "হ্যালো", "আসসালামু আলাইকুম"
3. Start DIRECTLY with the answer or information
4. Summarize the information in 2-4 paragraphs
5. Don't use structured formatting - write naturally like a summary
6. If multiple items found, weave them into your narrative
7. If no data found, politely say "এই বিষয়ে বর্তমানে কোনো তথ্য পাওয়া যায়নি"
8. Be conversational - use phrases like "তথ্য অনুযায়ী...", "বর্তমানে...", "পাওয়া তথ্যে দেখা যাচ্ছে..."
9. Don't just list facts - tell a story with the data
10. Be direct and concise - no unnecessary introductions

Example Good Response (NO greeting):
"বাংলাদেশ নির্বাচন কমিশন- এর তফসিল ঘোষণা হবে ১৫ ডিসেম্বর। এরপর ১০ থেকে ২৫ জানুয়ারির মধ্যে প্রার্থীদের মনোনয়নপত্র জমা দেওয়ার সুযোগ থাকবে। ৩০ জানুয়ারি হবে মনোনয়ন যাচাই-বাছাইয়ের দিন।"

Example BAD Response (avoid):
"নমস্কার! আপনি জানতে চেয়েছেন..." ❌

Now write your response in Bengali (NO greeting, start directly):
PROMPT;
    }

    /**
     * Prepare intelligent context from results
     */
    private function prepareIntelligentContext(array $results): string
    {
        $context = [];
        
        foreach ($results as $type => $data) {
            if (!empty($data) && count($data) > 0) {
                $context[] = $this->formatDataForContext($type, $data);
            }
        }
        
        return empty($context) ? "কোনো তথ্য পাওয়া যায়নি।" : implode("\n\n", $context);
    }

    /**
     * Format data for AI context
     */
    private function formatDataForContext(string $type, $data): string
    {
        switch ($type) {
            case 'candidates':
                $list = [];
                foreach ($data->take(5) as $c) {
                    $list[] = "- {$c->name} (দল: {$c->party?->name}, আসন: {$c->seat?->name}, বয়স: {$c->age}, শিক্ষা: {$c->education})";
                }
                return "প্রার্থী (" . count($data) . " জন):\n" . implode("\n", $list);
                
            case 'parties':
                $list = [];
                foreach ($data as $p) {
                    $list[] = "- {$p->name} (প্রতিষ্ঠা: {$p->founded}, প্রতীক: {$p->symbol?->symbol_name})";
                }
                return "রাজনৈতিক দল (" . count($data) . " টি):\n" . implode("\n", $list);
                
            case 'news':
                $list = [];
                foreach ($data->take(3) as $n) {
                    $summary = mb_substr($n->summary ?? $n->content, 0, 150) . '...';
                    $list[] = "- {$n->title}\n  {$summary}";
                }
                return "সর্বশেষ খবর (" . count($data) . " টি):\n" . implode("\n", $list);
                
            case 'seats':
                $list = [];
                foreach ($data->take(10) as $s) {
                    $list[] = "- {$s->name} ({$s->district?->name}): {$s->area}";
                }
                return "নির্বাচনী আসন (" . count($data) . " টি):\n" . implode("\n", $list);
                
            case 'timeline':
                $list = [];
                foreach ($data as $t) {
                    $list[] = "- {$t->title} ({$t->date}): {$t->status}";
                }
                return "সময়সূচী (" . count($data) . " টি):\n" . implode("\n", $list);
                
            case 'polls':
                $list = [];
                foreach ($data as $p) {
                    $list[] = "- {$p->question} (মোট ভোট: {$p->total_votes})";
                }
                return "জনমত জরিপ (" . count($data) . " টি):\n" . implode("\n", $list);
            
            case 'conduct_rules':
                $list = [];
                foreach ($data as $rule) {
                    $category = $rule->category;
                    $icon = match($category) {
                        'নিষিদ্ধ' => '🚫',
                        'অনুমোদিত' => '✅',
                        'নির্বাচনের দিন' => '📅',
                        'প্রার্থীর দায়িত্ব' => '👤',
                        'দলের দায়িত্ব' => '🏛️',
                        'শাস্তি' => '⚖️',
                        default => '📋'
                    };
                    $list[] = "{$icon} {$rule->title}\n  {$rule->description}";
                }
                return "নির্বাচনী আচরণবিধি (" . count($data) . " টি নিয়ম):\n" . implode("\n\n", $list);
                
            default:
                return "";
        }
    }

    /**
     * Generate basic response without AI
     */
    private function generateBasicResponse(array $results): string
    {
        $totalItems = 0;
        $summary = [];
        
        foreach ($results as $type => $data) {
            $count = is_countable($data) ? count($data) : 0;
            if ($count > 0) {
                $totalItems += $count;
                $typeNames = [
                    'candidates' => 'প্রার্থী',
                    'parties' => 'রাজনৈতিক দল',
                    'news' => 'খবর',
                    'seats' => 'আসন',
                    'polls' => 'জনমত জরিপ',
                    'timeline' => 'সময়সূচী',
                    'conduct_rules' => 'আচরণবিধি নিয়ম'
                ];
                $summary[] = "{$count} টি {$typeNames[$type]}";
            }
        }
        
        if ($totalItems === 0) {
            return "দুঃখিত, এই বিষয়ে বর্তমানে কোনো তথ্য পাওয়া যায়নি। অনুগ্রহ করে অন্য কোনো প্রশ্ন করুন।";
        }
        
        return "আপনার অনুসন্ধানে মোট " . implode(', ', $summary) . " পাওয়া গেছে।";
    }

    /**
     * Summarize results for response
     */
    private function summarizeResults(array $results): array
    {
        $summary = [];
        
        foreach ($results as $type => $data) {
            $count = is_countable($data) ? count($data) : 0;
            if ($count > 0) {
                $summary[$type] = $count;
            }
        }
        
        return $summary;
    }

    /**
     * OLD METHODS BELOW - Keep for backward compatibility
     */
    private function searchDatabase(string $query): array
    {
        $results = [
            'candidates' => [],
            'parties' => [],
            'news' => [],
            'polls' => [],
            'seats' => [],
            'timeline' => [],
        ];

        // Search candidates
        $results['candidates'] = Candidate::with(['party', 'seat.district'])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('name_en', 'LIKE', "%{$query}%")
                  ->orWhere('education', 'LIKE', "%{$query}%")
                  ->orWhere('experience', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'age' => $c->age,
                'education' => $c->education,
                'experience' => $c->experience,
                'party' => $c->party?->name,
                'seat' => $c->seat?->name,
                'district' => $c->seat?->district?->name,
            ]);

        // Search parties
        $results['parties'] = Party::with('symbol')
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('name_en', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'name_en' => $p->name_en,
                'symbol' => $p->symbol?->symbol_name ?? 'N/A',
                'founded' => $p->founded,
                'color' => $p->color,
            ]);

        // Search news
        $results['news'] = News::where('title', 'LIKE', "%{$query}%")
            ->orWhere('summary', 'LIKE', "%{$query}%")
            ->orWhere('content', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(fn($n) => [
                'uid' => $n->uid,
                'title' => $n->title,
                'summary' => $n->summary,
                'content' => $n->content,
                'published_date' => $n->date,
            ]);

        // Search polls
        $results['polls'] = Poll::with('options')
            ->where('question', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'uid' => $p->uid,
                'title' => $p->question, // Use question as title
                'question' => $p->question,
                'options' => $p->options->map(fn($o) => [
                    'option_text' => $o->text,
                    'votes_count' => $o->vote_count ?? 0,
                ])->toArray(),
                'total_votes' => $p->total_votes ?? 0,
            ]);
        $results['polls'] = Poll::where('question', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'uid' => $p->uid,
                'question' => $p->question,
                'total_votes' => $p->total_votes,
            ]);

        // Search seats
        $results['seats'] = Seat::with(['district'])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('name_en', 'LIKE', "%{$query}%")
                  ->orWhere('area', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'name_en' => $s->name_en,
                'area' => $s->area,
                'district' => $s->district?->name,
            ]);

        // Search timeline
        $results['timeline'] = TimelineEvent::where('title', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'date' => $t->event_date,
            ]);

        return $results;
    }

    /**
     * Generate AI response using Gemini
     */
    private function generateAiResponse(string $query, array $results): string
    {
        $geminiApiKey = config('services.gemini.api_key');
        
        if (!$geminiApiKey) {
            // Fallback response without AI
            return $this->generateFallbackResponse($results);
        }

        try {
            // Prepare context from database results
            $context = $this->prepareContextForAi($results);
            
            // Call Gemini API
            $response = Http::timeout(15)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiApiKey,
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $this->buildPrompt($query, $context)
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.8,
                        'topP' => 0.95,
                        'topK' => 40,
                        'maxOutputTokens' => 1024,
                    ],
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_HARASSMENT',
                            'threshold' => 'BLOCK_NONE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_HATE_SPEECH',
                            'threshold' => 'BLOCK_NONE'
                        ]
                    ]
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if ($aiText) {
                    return $aiText;
                }
            }
            
            // Fallback if API fails
            return $this->generateFallbackResponse($results);
            
        } catch (\Exception $e) {
            Log::error('Gemini API error: ' . $e->getMessage());
            return $this->generateFallbackResponse($results);
        }
    }

    /**
     * Build prompt for Gemini
     */
    private function buildPrompt(string $query, string $context): string
    {
        return <<<PROMPT
You are an intelligent AI assistant for Bangladesh Election 2026 information system. Answer user queries in Bengali (বাংলা) based on the database information provided.

User Query: "{$query}"

Database Information:
{$context}

Instructions:
1. Analyze the user's question carefully and provide a natural, conversational answer in Bengali
2. Use ONLY the information provided from the database - do not make up facts
3. If asking about a specific candidate/party/seat, provide detailed information
4. If asking a general question, synthesize the data into a helpful summary
5. If no relevant data found, politely say "দুঃখিত, এই বিষয়ে কোনো তথ্য পাওয়া যায়নি। অনুগ্রহ করে অন্য প্রশ্ন করুন।"
6. Keep response conversational (2-5 sentences) but informative
7. When mentioning numbers, include context (e.g., "ঢাকা-১ আসনে ৩ জন প্রার্থী প্রতিদ্বন্দ্বিতা করছেন")
8. If showing multiple items, present them in a clear, organized way
9. Answer in Bengali language only

Examples of good responses:
- Query: "ঢাকা-১ আসনে কারা প্রতিদ্বন্দ্বিতা করছে?" 
  Response: "ঢাকা-১ আসনে মোট ৩ জন প্রার্থী প্রতিদ্বন্দ্বিতা করছেন। তাঁরা হলেন: রহিম উদ্দিন (আওয়ামী লীগ), করিম মিয়া (বিএনপি), এবং সালমা খাতুন (স্বতন্ত্র)। এই আসনে ভোট অনুষ্ঠিত হবে ৫ জানুয়ারি ২০২৬।"

- Query: "আওয়ামী লীগ সম্পর্কে বলুন"
  Response: "আওয়ামী লীগ বাংলাদেশের অন্যতম প্রধান রাজনৈতিক দল। ২০২৬ সালের নির্বাচনে দলটি দেশব্যাপী ১৫০টি আসনে প্রার্থী মনোনয়ন দিয়েছে। দলের নির্বাচনী প্রতীক হলো নৌকা।"

Now provide your response in Bengali:
PROMPT;
    }

    /**
     * Prepare context from database results for AI
     */
    private function prepareContextForAi(array $results): string
    {
        $context = [];

        // Candidates - provide full details
        if (!empty($results['candidates']) && count($results['candidates']) > 0) {
            $candidatesList = [];
            foreach ($results['candidates'] as $candidate) {
                $candidatesList[] = sprintf(
                    "- %s (বয়স: %s, শিক্ষা: %s, অভিজ্ঞতা: %s)\n  দল: %s\n  আসন: %s (%s)",
                    $candidate['name'],
                    $candidate['age'] ?? 'N/A',
                    $candidate['education'] ?? 'N/A',
                    $candidate['experience'] ?? 'N/A',
                    $candidate['party'] ?? 'স্বতন্ত্র',
                    $candidate['seat'] ?? 'N/A',
                    $candidate['district'] ?? 'N/A'
                );
            }
            $context[] = "=== প্রার্থী তথ্য ===\nমোট " . count($results['candidates']) . " জন প্রার্থী পাওয়া গেছে:\n" . implode("\n", $candidatesList);
        }

        // Parties - provide full details
        if (!empty($results['parties']) && count($results['parties']) > 0) {
            $partiesList = [];
            foreach ($results['parties'] as $party) {
                $partiesList[] = sprintf(
                    "- %s (ইংরেজি: %s)\n  প্রতীক: %s\n  প্রতিষ্ঠা: %s\n  রঙ: %s",
                    $party['name'],
                    $party['name_en'] ?? 'N/A',
                    $party['symbol'] ?? 'N/A',
                    $party['founded'] ?? 'N/A',
                    $party['color'] ?? 'N/A'
                );
            }
            $context[] = "=== রাজনৈতিক দল ===\nমোট " . count($results['parties']) . " টি দল পাওয়া গেছে:\n" . implode("\n", $partiesList);
        }

        // News - provide headlines and summaries
        if (!empty($results['news']) && count($results['news']) > 0) {
            $newsList = [];
            foreach ($results['news'] as $news) {
                $summary = $news['summary'] ?? $news['content'] ?? 'বিস্তারিত নেই';
                $newsList[] = sprintf(
                    "- %s\n  সময়: %s\n  সারাংশ: %s",
                    $news['title'],
                    $news['published_date'] ?? 'N/A',
                    mb_substr($summary, 0, 150) . (mb_strlen($summary) > 150 ? '...' : '')
                );
            }
            $context[] = "=== সর্বশেষ খবর ===\nমোট " . count($results['news']) . " টি খবর পাওয়া গেছে:\n" . implode("\n", $newsList);
        }

        // Polls - provide poll details
        if (!empty($results['polls']) && count($results['polls']) > 0) {
            $pollsList = [];
            foreach ($results['polls'] as $poll) {
                $optionsText = isset($poll['options']) && is_array($poll['options']) 
                    ? implode(', ', array_column($poll['options'], 'option_text')) 
                    : 'N/A';
                $pollsList[] = sprintf(
                    "- %s\n  প্রশ্ন: %s\n  বিকল্প: %s\n  মোট ভোট: %s",
                    $poll['title'],
                    $poll['question'] ?? 'N/A',
                    $optionsText,
                    $poll['total_votes'] ?? '0'
                );
            }
            $context[] = "=== জনমত জরিপ ===\nমোট " . count($results['polls']) . " টি পোল পাওয়া গেছে:\n" . implode("\n", $pollsList);
        }

        // Seats - provide constituency details
        if (!empty($results['seats']) && count($results['seats']) > 0) {
            $seatsList = [];
            foreach ($results['seats'] as $seat) {
                $seatsList[] = sprintf(
                    "- %s (%s)\n  জেলা: %s\n  এলাকা: %s",
                    $seat['name'],
                    $seat['name_en'] ?? '',
                    $seat['district'] ?? 'N/A',
                    $seat['area'] ?? 'N/A'
                );
            }
            $context[] = "=== নির্বাচনী আসন ===\nমোট " . count($results['seats']) . " টি আসন পাওয়া গেছে:\n" . implode("\n", $seatsList);
        }

        // Timeline - provide event details
        if (!empty($results['timeline']) && count($results['timeline']) > 0) {
            $timelineList = [];
            foreach ($results['timeline'] as $event) {
                $timelineList[] = sprintf(
                    "- %s\n  তারিখ: %s\n  অবস্থা: %s",
                    $event['title'],
                    $event['event_date'] ?? 'N/A',
                    $event['status'] ?? 'N/A'
                );
            }
            $context[] = "=== নির্বাচনী সময়রেখা ===\nমোট " . count($results['timeline']) . " টি ইভেন্ট পাওয়া গেছে:\n" . implode("\n", $timelineList);
        }

        if (empty($context)) {
            return "কোনো তথ্য পাওয়া যায়নি।";
        }

        return implode("\n\n", $context);
    }

    /**
     * Generate fallback response without AI
     */
    private function generateFallbackResponse(array $results): string
    {
        $totalResults = collect($results)->sum(fn($r) => count($r));

        if ($totalResults === 0) {
            return "কিছু খুঁজে পাওয়া যায়নি, নির্বাচন সম্পর্কিত প্রশ্ন জিজ্ঞাসা করুন।";
        }

        $response = [];

        if (!empty($results['candidates'])) {
            $response[] = count($results['candidates']) . " জন প্রার্থী পাওয়া গেছে।";
        }
        if (!empty($results['parties'])) {
            $response[] = count($results['parties']) . " টি রাজনৈতিক দল পাওয়া গেছে।";
        }
        if (!empty($results['news'])) {
            $response[] = count($results['news']) . " টি খবর পাওয়া গেছে।";
        }
        if (!empty($results['polls'])) {
            $response[] = count($results['polls']) . " টি ভোট পাওয়া গেছে।";
        }

        return implode(" ", $response);
    }
}
