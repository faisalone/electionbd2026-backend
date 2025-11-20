<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VertexAiAgentService
{
    private string $projectId;
    private string $location;
    private string $dataStoreId;
    private string $serviceAccountPath;
    
    public function __construct()
    {
        $this->projectId = config('services.google_cloud.project_id', '923148411282');
        $this->location = config('services.google_cloud.location', 'global');
        $this->dataStoreId = config('services.google_cloud.datastore_id', 'bd26-election-agent_1763640025270');
        $this->serviceAccountPath = config('services.google_cloud.service_account_path', storage_path('app/google-cloud-key.json'));
    }
    
    /**
     * Search conduct rules using Vertex AI Agent Builder
     */
    public function searchConductRules(string $query, int $pageSize = 10): ?array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                Log::warning('Vertex AI: No access token available');
                return null;
            }
            
            $endpoint = "https://discoveryengine.googleapis.com/v1alpha/projects/{$this->projectId}/locations/{$this->location}/collections/default_collection/engines/{$this->dataStoreId}/servingConfigs/default_search:search";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(15)
            ->post($endpoint, [
                'query' => $query . ' (অনুগ্রহ করে বিধি নম্বর এবং রেফারেন্স সহ উত্তর দিন)',
                'pageSize' => $pageSize,
                'queryExpansionSpec' => [
                    'condition' => 'AUTO'
                ],
                'spellCorrectionSpec' => [
                    'mode' => 'AUTO'
                ],
                'languageCode' => 'bn-BD', // Bengali
                'contentSearchSpec' => [
                    'snippetSpec' => [
                        'returnSnippet' => true,
                        'maxSnippetCount' => 5,
                    ],
                    'summarySpec' => [
                        'summaryResultCount' => 10,
                        'includeCitations' => true,
                        'ignoreAdversarialQuery' => true,
                        'ignoreNonSummarySeekingQuery' => true,
                        'modelPromptSpec' => [
                            'preamble' => 'আপনি বাংলাদেশ নির্বাচন কমিশনের নির্বাচনী আচরণবিধি বিশেষজ্ঞ। প্রতিটি উত্তরে অবশ্যই বিধি নম্বর (যেমন: বিধি ৪, বিধি ৭(ক)) এবং সূত্র উল্লেখ করুন।'
                        ]
                    ]
                ],
                'userInfo' => [
                    'timeZone' => 'Asia/Dhaka'
                ]
            ]);
            
            if ($response->successful()) {
                return $this->parseResponse($response->json());
            }
            
            Log::error('Vertex AI search failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Vertex AI search error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get OAuth2 access token from service account
     */
    private function getAccessToken(): ?string
    {
        // Check cache first (tokens are valid for ~1 hour)
        $cacheKey = 'vertex_ai_access_token';
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        if (!file_exists($this->serviceAccountPath)) {
            Log::warning('Google Cloud service account key not found at: ' . $this->serviceAccountPath);
            return null;
        }
        
        try {
            $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
            
            if (!$serviceAccount || !isset($serviceAccount['private_key']) || !isset($serviceAccount['client_email'])) {
                Log::error('Invalid service account JSON format');
                return null;
            }
            
            // Create JWT
            $now = time();
            $jwt = [
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];
            
            $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $jwtPayload = base64_encode(json_encode($jwt));
            $jwtSignature = '';
            
            openssl_sign(
                "{$jwtHeader}.{$jwtPayload}",
                $jwtSignature,
                $serviceAccount['private_key'],
                OPENSSL_ALGO_SHA256
            );
            
            $jwtSignature = base64_encode($jwtSignature);
            $assertion = "{$jwtHeader}.{$jwtPayload}.{$jwtSignature}";
            
            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];
                
                // Cache for 50 minutes (token valid for 60)
                Cache::put($cacheKey, $accessToken, now()->addMinutes(50));
                
                return $accessToken;
            }
            
            Log::error('Failed to get access token', ['response' => $response->body()]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Access token generation error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Parse Vertex AI response
     */
    private function parseResponse(array $response): array
    {
        $results = [];
        
        // Extract summary if available
        if (isset($response['summary']['summaryText'])) {
            $results['summary'] = $response['summary']['summaryText'];
        }
        
        // Extract search results
        if (isset($response['results'])) {
            $results['documents'] = [];
            
            foreach ($response['results'] as $result) {
                $document = $result['document'] ?? [];
                
                $results['documents'][] = [
                    'id' => $document['id'] ?? null,
                    'title' => $document['structData']['title'] ?? null,
                    'content' => $document['structData']['content'] ?? null,
                    'snippet' => $result['document']['derivedStructData']['snippets'][0]['snippet'] ?? null,
                    'link' => $document['structData']['link'] ?? null,
                ];
            }
        }
        
        // Total results
        $results['totalSize'] = $response['totalSize'] ?? 0;
        
        return $results;
    }
    
    /**
     * Check if Vertex AI is configured and available
     */
    public function isAvailable(): bool
    {
        return file_exists($this->serviceAccountPath) && 
               !empty($this->projectId) && 
               !empty($this->dataStoreId);
    }
    
    /**
     * Test the connection
     */
    public function testConnection(): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get access token. Check service account key.',
                ];
            }
            
            $results = $this->searchConductRules('নিষিদ্ধ', 1);
            
            if ($results) {
                return [
                    'success' => true,
                    'message' => 'Vertex AI Agent is working!',
                    'sample_results' => $results,
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Connection successful but no results returned.',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
}
