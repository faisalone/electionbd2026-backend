<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Party;
use App\Models\Seat;
use App\Models\Symbol;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    private $firstNames = [
        'আব্দুল', 'মোহাম্মদ', 'আহমেদ', 'মাহমুদ', 'রহিম', 'করিম', 'সালাম', 'জাহান',
        'নাসরিন', 'সুলতানা', 'রোকেয়া', 'ফাতেমা', 'আয়েশা', 'খাদিজা', 'মরিয়ম', 'জান্নাত'
    ];

    private $lastNames = [
        'আলী', 'হোসেন', 'রহমান', 'ইসলাম', 'খান', 'চৌধুরী', 'মিয়া', 'শেখ',
        'বেগম', 'খাতুন', 'আক্তার', 'পারভীন', 'সিদ্দিকা', 'নাহার', 'হক', 'মল্লিক'
    ];

    private $firstNamesEn = [
        'Abdul', 'Mohammad', 'Ahmed', 'Mahmud', 'Rahim', 'Karim', 'Salam', 'Jahan',
        'Nasrin', 'Sultana', 'Rokeya', 'Fatema', 'Ayesha', 'Khadija', 'Mariam', 'Jannat'
    ];

    private $lastNamesEn = [
        'Ali', 'Hossain', 'Rahman', 'Islam', 'Khan', 'Chowdhury', 'Mia', 'Sheikh',
        'Begum', 'Khatun', 'Akhter', 'Parveen', 'Siddiqua', 'Nahar', 'Hoque', 'Mollick'
    ];

    public function run(): void
    {
        $seats = Seat::all();
        $parties = Party::all();
        $symbols = Symbol::where('is_available', true)->get();

        foreach ($seats as $seat) {
            // Shuffle parties and get 2-4 random parties for this seat
            $selectedParties = $parties->shuffle()->take(rand(2, min(4, $parties->count())));

            foreach ($selectedParties as $party) {
                // Generate random name
                $firstNameBn = $this->firstNames[array_rand($this->firstNames)];
                $lastNameBn = $this->lastNames[array_rand($this->lastNames)];
                $firstNameEn = $this->firstNamesEn[array_rand($this->firstNamesEn)];
                $lastNameEn = $this->lastNamesEn[array_rand($this->lastNamesEn)];

                $candidate = [
                    'seat_id' => $seat->id,
                    'party_id' => $party->id,
                    'name' => $firstNameBn . ' ' . $lastNameBn,
                    'name_en' => $firstNameEn . ' ' . $lastNameEn,
                    'age' => rand(30, 65),
                    'education' => $this->getRandomEducation(),
                    'experience' => $this->getRandomExperience(),
                ];

                // If independent, assign a symbol
                if ($party->is_independent && $symbols->count() > 0) {
                    $candidate['symbol_id'] = $symbols->random()->id;
                }

                Candidate::create($candidate);
            }
        }

        $this->command->info('Candidates seeded successfully! Total: ' . Candidate::count());
    }

    private function getRandomEducation(): string
    {
        $educations = [
            'স্নাতক',
            'স্নাতকোত্তর',
            'এমবিবিএস',
            'এলএলবি',
            'বিবিএ',
            'এমবিএ',
            'বিএসসি ইঞ্জিনিয়ারিং',
        ];
        return $educations[array_rand($educations)];
    }

    private function getRandomExperience(): string
    {
        $experiences = [
            'ব্যবসায়ী',
            'শিক্ষক',
            'আইনজীবী',
            'চিকিৎসক',
            'সমাজসেবক',
            'প্রাক্তন সরকারি কর্মকর্তা',
            'সাংবাদিক',
        ];
        return $experiences[array_rand($experiences)];
    }
}
