<?php

namespace App\Console\Commands;

use App\Services\NewsGenerationService;
use Illuminate\Console\Command;

class GenerateNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:generate 
                            {--topic= : Specific topic to generate news for}
                            {--all : Generate news for all configured topics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AI news articles about Bangladesh elections';

    /**
     * Execute the console command.
     */
    public function handle(NewsGenerationService $service): int
    {
        $this->info('🚀 Starting Bangladesh Election News Generation...');
        $this->newLine();

        $topic = $this->option('topic');
        $all = $this->option('all');

        if ($topic) {
            // Generate for specific topic
            $this->info("Generating article for topic: {$topic}");
            $result = $service->generateArticleForTopic($topic);
            $this->displayResult($result);
        } elseif ($all) {
            // Generate for all topics
            $this->info('Generating articles for all configured topics...');
            $results = $service->generateDailyNews();
            
            $this->newLine();
            $this->info('📊 Summary:');
            $this->displaySummary($results);
        } else {
            $this->error('Please specify either --topic or --all option');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ News generation completed!');

        return self::SUCCESS;
    }

    /**
     * Display result for a single article generation.
     */
    private function displayResult(array $result): void
    {
        $this->newLine();
        
        if ($result['success']) {
            $this->components->success('Article generated successfully!');
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Topic', $result['topic']],
                    ['Sources Found', $result['sources_count'] ?? 'N/A'],
                    ['Published', $result['published'] ? 'Yes' : 'No'],
                    ['Article UID', $result['article_uid'] ?? 'N/A'],
                    ['Message', $result['message'] ?? ''],
                ]
            );
        } else {
            $this->components->error('Failed to generate article');
            $this->warn('Reason: ' . ($result['message'] ?? $result['error'] ?? 'Unknown error'));
        }
    }

    /**
     * Display summary for multiple article generations.
     */
    private function displaySummary(array $results): void
    {
        $total = count($results);
        $successful = collect($results)->where('success', true)->count();
        $published = collect($results)->where('published', true)->count();
        $failed = $total - $successful;

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Attempts', $total],
                ['Successful', $successful],
                ['Published', $published],
                ['Duplicates Skipped', $successful - $published],
                ['Failed', $failed],
            ]
        );

        $this->newLine();
        $this->info('📰 Details:');
        
        foreach ($results as $result) {
            $status = $result['success'] 
                ? ($result['published'] ?? false ? '✅ Published' : '⚠️  Duplicate') 
                : '❌ Failed';
            
            $this->line(sprintf(
                "  %s - %s %s",
                $status,
                $result['topic'],
                isset($result['article_uid']) ? "({$result['article_uid']})" : ''
            ));
        }
    }
}
