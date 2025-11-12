<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CandidateSeeder extends Seeder
{
    /**
     * Parse and seed candidates from text files
     * Files: database/data/bnp.txt, database/data/jamat.txt, database/data/ncp.txt
     */
    public function run(): void
    {
        // Set UTF-8 encoding for multibyte string functions
        mb_internal_encoding('UTF-8');
        
        // Get party IDs
        $parties = DB::table('parties')
            ->select('id', 'name_en')
            ->get()
            ->keyBy('name_en');

        $bnpId = $parties->get('Bangladesh Nationalist Party (BNP)')?->id ?? null;
        $jamatId = $parties->get('Bangladesh Jamaat-e-Islami')?->id ?? null;
        $ncpId = $parties->get('National Citizens Party (NCP)')?->id ?? null;

        if (!$bnpId || !$jamatId || !$ncpId) {
            $this->command->error('Parties not found! Please seed parties first.');
            return;
        }

        // Get all seats for mapping
        $seats = DB::table('seats')
            ->join('districts', 'seats.district_id', '=', 'districts.id')
            ->select('seats.id', 'districts.name as district_name', 'seats.name as seat_name')
            ->get();

        // Build a mapping: "district_number" => seat_id
        // Extract seat number from seat name (e.g., "পঞ্চগড়-১" -> 1)
        $seatMap = [];
        foreach ($seats as $seat) {
            // Extract seat number from seat name
            $seatInfo = $this->extractSeatInfo($seat->seat_name);
            if ($seatInfo) {
                $key = $this->normalizeSeatKey($seatInfo['district'], $seatInfo['number']);
                $seatMap[$key] = $seat->id;
            }
        }

        $candidates = [];
        $skipped = [];

        // Parse BNP candidates
        $this->command->info('Parsing BNP candidates...');
        $bnpData = $this->parseBNPFile($seatMap, $bnpId, $skipped);
        $candidates = array_merge($candidates, $bnpData);
        $this->command->info('BNP: ' . count($bnpData) . ' candidates parsed');

        // Parse Jamaat candidates
        $this->command->info('Parsing Jamaat candidates...');
        $jamatData = $this->parseJamatFile($seatMap, $jamatId, $skipped);
        $candidates = array_merge($candidates, $jamatData);
        $this->command->info('Jamaat: ' . count($jamatData) . ' candidates parsed');

        // Parse NCP candidates
        $this->command->info('Parsing NCP candidates...');
        $ncpData = $this->parseNCPFile($seatMap, $ncpId, $skipped);
        $candidates = array_merge($candidates, $ncpData);
        $this->command->info('NCP: ' . count($ncpData) . ' candidates parsed');

        // Set connection charset
        DB::statement('SET NAMES utf8mb4');
        DB::statement('SET CHARACTER SET utf8mb4');

        // Insert candidates in small batches with error handling
        if (!empty($candidates)) {
            $chunks = array_chunk($candidates, 50);
            $successCount = 0;
            
            foreach ($chunks as $chunkIndex => $chunk) {
                try {
                    DB::table('candidates')->insert($chunk);
                    $successCount += count($chunk);
                } catch (\Exception $e) {
                    // If batch fails, try inserting one by one
                    $this->command->warn('Batch ' . ($chunkIndex + 1) . ' failed, trying individually...');
                    foreach ($chunk as $candidate) {
                        try {
                            DB::table('candidates')->insert($candidate);
                            $successCount++;
                        } catch (\Exception $innerE) {
                            $this->command->error('Failed to insert: ' . $candidate['name']);
                        }
                    }
                }
            }
            
            $this->command->info('Successfully seeded ' . $successCount . ' out of ' . count($candidates) . ' candidates!');
        }

        // Report skipped entries
        if (!empty($skipped)) {
            $this->command->warn('Skipped ' . count($skipped) . ' entries:');
            foreach (array_slice($skipped, 0, 10) as $skip) {
                $this->command->line('  - ' . $skip);
            }
            if (count($skipped) > 10) {
                $this->command->line('  ... and ' . (count($skipped) - 10) . ' more');
            }
        }
    }

    private function parseBNPFile(array $seatMap, int $partyId, array &$skipped): array
    {
        $filePath = database_path('data/bnp.txt');
        if (!file_exists($filePath)) {
            $this->command->error("BNP file not found: $filePath");
            return [];
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $candidates = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Skip headers and division names
            if (
                str_contains($line, 'বাংলাদেশ জাতীয়তাবাদী দল') ||
                str_contains($line, 'বিভাগ') ||
                str_contains($line, 'সম্ভাব্য প্রার্থী')
            ) {
                continue;
            }

            // Parse line format: "আসন-নাম: প্রার্থী নাম"
            if (preg_match('/^([^\:]+)\:(.+)$/', $line, $matches)) {
                $seatPart = trim($matches[1]);
                $candidateName = trim($matches[2]);

                // Skip if on hold or TBD
                if (
                    str_contains($candidateName, 'হোল্ড করা') ||
                    str_contains($candidateName, 'পরে সিদ্ধান্ত') ||
                    str_contains($candidateName, 'পরে ঘোষণা') ||
                    str_contains($candidateName, 'পরে জানানো')
                ) {
                    continue;
                }

                // Clean candidate name (remove notes in parentheses at the end)
                $candidateName = preg_replace('/\s*\([^)]*\)\s*$/', '', $candidateName);
                $candidateName = trim($candidateName);

                if (empty($candidateName)) {
                    continue;
                }

                // Extract district and seat number
                $seatInfo = $this->extractSeatInfo($seatPart);
                if (!$seatInfo) {
                    $skipped[] = "BNP: Could not parse seat - $line";
                    continue;
                }

                $seatKey = $this->normalizeSeatKey($seatInfo['district'], $seatInfo['number']);
                $seatId = $seatMap[$seatKey] ?? null;

                if (!$seatId) {
                    $skipped[] = "BNP: Seat not found - $seatKey ($line)";
                    continue;
                }

                $candidates[] = [
                    'name' => $candidateName,
                    'name_en' => 'N/A',
                    'seat_id' => $seatId,
                    'party_id' => $partyId,
                    'symbol_id' => null,
                    'age' => 0,
                    'education' => 'N/A',
                    'experience' => null,
                    'image' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $candidates;
    }

    private function parseJamatFile(array $seatMap, int $partyId, array &$skipped): array
    {
        $filePath = database_path('data/jamat.txt');
        if (!file_exists($filePath)) {
            $this->command->error("Jamaat file not found: $filePath");
            return [];
        }

        $content = file_get_contents($filePath);
        // Normalize line endings and split
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        $candidates = [];

        foreach ($lines as $lineNum => $line) {
            // Remove any control characters except newline
            $line = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/u', '', $line);
            $line = trim($line);
            if (empty($line)) continue;

            // Skip headers and division summaries
            if (str_contains($line, 'বিভাগ-')) {
                continue;
            }

            // Parse format: "আসন-নাম প্রার্থী নাম (পদবি)"
            // Example: "পঞ্চগড়-১ অধ্যাপক ইকবাল হোসেন"
            if (preg_match('/^([^\s]+[-০-৯১-৯\d]+)\s+(.+)$/u', $line, $matches)) {
                $seatPart = trim($matches[1]);
                $candidateName = trim($matches[2]);

                // Remove trailing comma or period
                $candidateName = rtrim($candidateName, ',।.');

                if (empty($candidateName)) {
                    continue;
                }

                // Extract district and seat number
                $seatInfo = $this->extractSeatInfo($seatPart);
                if (!$seatInfo) {
                    $skipped[] = "Jamaat: Could not parse seat - $line";
                    continue;
                }

                $seatKey = $this->normalizeSeatKey($seatInfo['district'], $seatInfo['number']);
                $seatId = $seatMap[$seatKey] ?? null;

                if (!$seatId) {
                    $skipped[] = "Jamaat: Seat not found - $seatKey ($line)";
                    continue;
                }

                // Skip corrupted entry - will be manually added
                // কুমিল্লা-১০ মাওলানা ইয়াসিন আরাফাত has encoding issue
                if (str_contains($candidateName, 'আরাফা') && $seatId == 258) {
                    // Manually add the correct version
                    $candidates[] = [
                        'name' => 'মাওলানা ইয়াসিন আরাফাত',
                        'name_en' => 'N/A',
                        'seat_id' => $seatId,
                        'party_id' => $partyId,
                        'symbol_id' => null,
                        'age' => 0,
                        'education' => 'N/A',
                        'experience' => null,
                        'image' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    continue;
                }

                $candidates[] = [
                    'name' => $candidateName,
                    'name_en' => 'N/A',
                    'seat_id' => $seatId,
                    'party_id' => $partyId,
                    'symbol_id' => null,
                    'age' => 0,
                    'education' => 'N/A',
                    'experience' => null,
                    'image' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $candidates;
    }

    private function parseNCPFile(array $seatMap, int $partyId, array &$skipped): array
    {
        // Define NCP candidates manually based on the descriptive text
        $ncpCandidates = [
            ['seat' => 'ঢাকা-১১', 'name' => 'নাহিদ ইসলাম'],
            ['seat' => 'ঢাকা-১৮', 'name' => 'নাসীররুদ্দীন পাটওয়ারী'],
            ['seat' => 'নরসিংদি-২', 'name' => 'সারোয়ার তুষার'],
            ['seat' => 'রংপুর-৪', 'name' => 'আখতার হোসেন'],
            ['seat' => 'পঞ্চগড়-১', 'name' => 'সারজিস আলম'],
        ];

        $candidates = [];

        foreach ($ncpCandidates as $candidate) {
            $seatInfo = $this->extractSeatInfo($candidate['seat']);
            if (!$seatInfo) {
                $skipped[] = "NCP: Could not parse seat - {$candidate['seat']}";
                continue;
            }

            $seatKey = $this->normalizeSeatKey($seatInfo['district'], $seatInfo['number']);
            $seatId = $seatMap[$seatKey] ?? null;

            if (!$seatId) {
                $skipped[] = "NCP: Seat not found - $seatKey";
                continue;
            }

            $candidates[] = [
                'name' => $candidate['name'],
                'name_en' => 'N/A',
                'seat_id' => $seatId,
                'party_id' => $partyId,
                'symbol_id' => null,
                'age' => 0,
                'education' => 'N/A',
                'experience' => null,
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $candidates;
    }

    /**
     * Extract district name and seat number from text like "পঞ্চগড়-১" or "পঞ্চগড়_1" or "ঢাকা-১১"
     */
    private function extractSeatInfo(string $seatText): ?array
    {
        // Convert Bengali numerals to English
        $seatText = $this->convertBengaliToEnglish($seatText);

        // Pattern: "district-number" or "district_number" (support both hyphen and underscore)
        if (preg_match('/^([^\-_]+)[-_](\d+)$/', $seatText, $matches)) {
            return [
                'district' => trim($matches[1]),
                'number' => (int)$matches[2],
            ];
        }

        return null;
    }

    /**
     * Normalize seat key for mapping: "district_number"
     */
    private function normalizeSeatKey(string $district, int $number): string
    {
        // Normalize district name and apply Unicode NFC normalization
        $district = trim($district);
        // Normalize Unicode to NFC form (precomposed characters)
        if (class_exists('Normalizer')) {
            $district = \Normalizer::normalize($district, \Normalizer::FORM_C);
        }
        return "{$district}_{$number}";
    }

    /**
     * Convert Bengali numerals (০-৯) to English (0-9)
     */
    private function convertBengaliToEnglish(string $text): string
    {
        $bengaliNumerals = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($bengaliNumerals, $englishNumerals, $text);
    }
}
