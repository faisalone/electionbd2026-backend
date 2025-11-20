<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VertexAiAgentService;

class TestVertexAi extends Command
{
    protected $signature = 'vertex:test {query?}';
    protected $description = 'Test Vertex AI Agent Builder connection';

    public function handle(VertexAiAgentService $vertexAi)
    {
        $this->info('🧪 Testing Vertex AI Agent Builder...');
        $this->newLine();
        
        // Check if configured
        if (!$vertexAi->isAvailable()) {
            $this->error('❌ Vertex AI is not configured properly.');
            $this->warn('Please ensure:');
            $this->line('  1. Service account JSON key is at: ' . storage_path('app/google-cloud-key.json'));
            $this->line('  2. Or set GOOGLE_CLOUD_SERVICE_ACCOUNT_PATH in .env');
            $this->newLine();
            $this->info('To download service account key:');
            $this->line('  1. Go to: https://console.cloud.google.com/iam-admin/serviceaccounts');
            $this->line('  2. Select your project');
            $this->line('  3. Create/Select a service account');
            $this->line('  4. Create key → JSON → Download');
            $this->line('  5. Save as: storage/app/google-cloud-key.json');
            return 1;
        }
        
        $this->info('✅ Vertex AI configuration found');
        $this->newLine();
        
        // Test connection
        $this->info('Testing connection...');
        $result = $vertexAi->testConnection();
        
        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            
            if (isset($result['sample_results'])) {
                $this->newLine();
                $this->info('Sample results:');
                $this->line(json_encode($result['sample_results'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } else {
            $this->error('❌ ' . $result['message']);
            return 1;
        }
        
        // Custom query test
        $query = $this->argument('query');
        
        if ($query) {
            $this->newLine();
            $this->info("Testing with query: {$query}");
            $this->newLine();
            
            $results = $vertexAi->searchConductRules($query, 5);
            
            if ($results) {
                if (isset($results['summary'])) {
                    $this->info('📝 Summary:');
                    $this->line($results['summary']);
                    $this->newLine();
                }
                
                if (isset($results['documents']) && count($results['documents']) > 0) {
                    $this->info('📚 Found ' . count($results['documents']) . ' documents:');
                    $this->newLine();
                    
                    foreach ($results['documents'] as $i => $doc) {
                        $this->line(($i + 1) . '. ' . ($doc['title'] ?? 'No title'));
                        if (!empty($doc['snippet'])) {
                            $this->line('   ' . $doc['snippet']);
                        }
                        $this->newLine();
                    }
                    
                    $this->info('Total results: ' . ($results['totalSize'] ?? 0));
                } else {
                    $this->warn('No documents found');
                }
            } else {
                $this->error('No results returned');
            }
        }
        
        return 0;
    }
}
