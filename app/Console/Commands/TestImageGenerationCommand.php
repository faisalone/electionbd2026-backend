<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestImageGenerationCommand extends Command
{
    protected $signature = 'news:test-image {--prompt= : Custom prompt for testing}';
    protected $description = 'Test image generation using Gemini 2.5 Flash Image model';

    public function handle(): int
    {
        $this->info('🎨 Testing Gemini 2.5 Flash Image Model for News Image Generation');
        $this->newLine();

        // Check API key
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            $this->error('❌ GEMINI_API_KEY not configured in .env');
            return self::FAILURE;
        }

        $this->info('✅ API Key found');
        $this->newLine();

        // Get custom prompt or use default
        $customPrompt = $this->option('prompt');
        $prompt = $customPrompt ?: 'A professional photojournalistic image of a Bangladesh election scene, showing voters at a polling station, documentary photography style, high quality, HDR, realistic, photorealistic, professional journalism, news photography, wide angle, natural lighting';

        $this->info('📝 Image Prompt:');
        $this->line($prompt);
        $this->newLine();

        try {
            $this->info('🔄 Calling Gemini 2.5 Flash Image API...');
            
            $model = 'gemini-2.5-flash-image';
            
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey,
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['Image'],
                        'imageConfig' => [
                            'aspectRatio' => '16:9',
                        ],
                    ],
                ]
            );

            if (!$response->successful()) {
                $this->error('❌ Gemini API Error');
                $this->error('Status: ' . $response->status());
                $this->error('Response: ' . $response->body());
                return self::FAILURE;
            }

            $data = $response->json();
            
            if (!isset($data['candidates'][0]['content']['parts'][0]['inlineData'])) {
                $this->error('❌ No image data in response');
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
                return self::FAILURE;
            }

            $this->info('✅ Image generated successfully!');
            $this->newLine();

            // Decode and save image
            $inlineData = $data['candidates'][0]['content']['parts'][0]['inlineData'];
            $imageBytes = $inlineData['data'];
            $mimeType = $inlineData['mimeType'] ?? 'image/png';
            
            $imageData = base64_decode($imageBytes);
            
            if (!$imageData) {
                $this->error('❌ Failed to decode image bytes');
                return self::FAILURE;
            }

            // Determine extension
            $extension = match($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'png',
            };

            // Generate filename
            $filename = 'test_news_' . time() . '_' . Str::random(10) . '.' . $extension;
            $path = 'news/' . $filename;
            
            // Ensure directory exists
            $fullPath = storage_path('app/public/' . $path);
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
                $this->info('📁 Created directory: ' . $directory);
            }

            // Save image
            Storage::disk('public')->put($path, $imageData);
            
            $publicUrl = '/storage/' . $path;
            $absolutePath = storage_path('app/public/' . $path);
            
            $this->info('💾 Image saved successfully!');
            $this->newLine();
            
            $this->table(
                ['Property', 'Value'],
                [
                    ['Filename', $filename],
                    ['Storage Path', $path],
                    ['Public URL', $publicUrl],
                    ['Absolute Path', $absolutePath],
                    ['File Size', $this->formatBytes(strlen($imageData))],
                    ['MIME Type', $mimeType],
                    ['Model Used', $model],
                ]
            );

            $this->newLine();
            $this->info('🌐 View in browser:');
            $this->line('http://localhost:8000' . $publicUrl);
            $this->newLine();
            
            $this->info('✅ Test completed successfully!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
