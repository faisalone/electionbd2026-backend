<?php

namespace App\Console\Commands;

use App\Services\NewsGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestNewsCommand extends Command
{
    protected $signature = 'news:test {--full : Run full integration test}';
    protected $description = 'Test the news generation system';

    public function handle(): int
    {
        $this->info('🧪 Testing News Generation System...');
        $this->newLine();

        // Test 1: Configuration
        $this->testConfiguration();

        // Test 2: Google API
        $this->testGoogleAPI();

        // Test 3: AI Provider
        $this->testAIProvider();

        // Test 4: Database
        $this->testDatabase();

        if ($this->option('full')) {
            $this->newLine();
            $this->info('🔄 Running full integration test...');
            $this->testFullGeneration();
        }

        $this->newLine();
        $this->info('✅ System test completed!');
        $this->info('💡 Run: php artisan news:generate --all');

        return self::SUCCESS;
    }

    private function testConfiguration(): void
    {
        $this->info('1️⃣ Configuration');

        $config = [
            ['Setting', 'Status'],
            ['AI Provider', config('services.ai.provider', 'gemini')],
            ['Gemini API', config('services.gemini.api_key') ? '✅' : '❌'],
            ['Google API', config('services.google.api_key') ? '✅' : '❌'],
            ['Search Engine', config('services.google.search_engine_id') ? '✅' : '❌'],
        ];

        $this->table($config[0], array_slice($config, 1));
        $this->newLine();
    }

    private function testGoogleAPI(): void
    {
        $this->info('2️⃣ Google Custom Search API');

        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/customsearch/v1', [
                'key' => config('services.google.api_key'),
                'cx' => config('services.google.search_engine_id'),
                'q' => 'নির্বাচন',
                'num' => 3,
                'dateRestrict' => 'd1',
            ]);

            if ($response->successful()) {
                $count = count($response->json()['items'] ?? []);
                $this->info("   ✅ Found {$count} results");
            } else {
                $this->error('   ❌ API Error: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function testAIProvider(): void
    {
        $provider = config('services.ai.provider', 'gemini');
        $this->info("3️⃣ AI Provider ({$provider})");

        try {
            if ($provider === 'gemini') {
                $model = config('services.gemini.model', 'gemini-2.5-flash');
                $response = Http::timeout(10)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . config('services.gemini.api_key'),
                    [
                        'contents' => [
                            ['parts' => [['text' => 'Say "test successful" in Bengali JSON: {"message": "..."}']]]
                        ],
                    ]
                );

                if ($response->successful()) {
                    $this->info('   ✅ Gemini API working');
                } else {
                    $this->error('   ❌ Gemini Error: ' . $response->status());
                }
            } else {
                $this->info('   ⚠️  OpenAI test not implemented');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function testDatabase(): void
    {
        $this->info('4️⃣ Database Connection');

        try {
            \DB::connection()->getPdo();
            $newsCount = \App\Models\News::count();
            $this->info("   ✅ Connected (News: {$newsCount})");
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function testFullGeneration(): void
    {
        $service = app(NewsGenerationService::class);
        
        $this->info('Testing article generation for: নির্বাচন');
        
        try {
            $result = $service->generateArticleForTopic('নির্বাচন');
            
            if ($result['success']) {
                $this->info('✅ Article generated successfully!');
                $this->info('   UID: ' . ($result['article_uid'] ?? 'N/A'));
            } else {
                $this->warn('⚠️  ' . $result['message']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
