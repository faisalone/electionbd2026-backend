<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;

class ViewLatestNewsCommand extends Command
{
    protected $signature = 'news:latest {--ai : Show only AI-generated articles}';
    protected $description = 'View the latest news articles';

    public function handle(): int
    {
        $query = News::orderBy('created_at', 'desc');
        
        if ($this->option('ai')) {
            $query->where('is_ai_generated', true);
        }
        
        $news = $query->take(3)->get();

        if ($news->isEmpty()) {
            $this->warn('No articles found');
            return self::SUCCESS;
        }

        foreach ($news as $article) {
            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('🆔 UID: ' . ($article->uid ?? 'N/A'));
            $this->line('📰 Title: ' . $article->title);
            $this->line('📝 Summary: ' . substr($article->summary, 0, 150) . (strlen($article->summary) > 150 ? '...' : ''));
            $this->line('📂 Category: ' . $article->category);
            $this->line('📅 Date: ' . $article->date);
            $this->line('🤖 AI Generated: ' . ($article->is_ai_generated ? 'Yes' : 'No'));
            $this->line('🕐 Created: ' . $article->created_at->format('Y-m-d H:i:s'));
            $this->line('📊 Content Length: ' . mb_strlen($article->content) . ' characters');
            
            if ($article->source_url) {
                $sources = json_decode($article->source_url, true);
                if (is_array($sources)) {
                    $this->line('🔗 Sources: ' . count($sources) . ' sources');
                }
            }
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        return self::SUCCESS;
    }
}
