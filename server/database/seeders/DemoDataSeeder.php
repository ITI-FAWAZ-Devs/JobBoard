<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\EmployerProfile;
use App\Models\CandidateProfile;
use App\Models\JobListing;
use App\Models\Category;
use App\Models\Application;
use App\Models\Comment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Check if categories exist
        if (Category::count() === 0) {
            $this->call(CategorySeeder::class);
        }
        $categoryIds = Category::pluck('id')->toArray();

        // 1. Admin
        User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. Employer
        $employerUser = User::firstOrCreate(
            ['email' => 'employer@demo.com'],
            [
                'name' => 'TechCorp HR',
                'password' => Hash::make('password'),
                'role' => 'employer',
            ]
        );

        $employerProfile = EmployerProfile::firstOrCreate(
            ['user_id' => $employerUser->id],
            [
                'company_name' => 'TechCorp Innovations',
                'website' => 'https://techcorp.example.com',
                'location' => 'San Francisco, CA',
                'description' => 'A leading tech company.',
            ]
        );

        // 3. Candidates
        $candidates = [];
        for ($i = 1; $i <= 10; $i++) {
            $candidateUser = User::firstOrCreate(
                ['email' => "candidate{$i}@demo.com"],
                [
                    'name' => "Candidate $i",
                    'password' => Hash::make('password'),
                    'role' => 'candidate',
                ]
            );

            CandidateProfile::firstOrCreate(
                ['user_id' => $candidateUser->id],
                [
                    'skills' => ['JavaScript', 'Vue.js', 'Laravel', 'PHP', 'Tailwind'],
                    'bio' => 'Passionate about coding.',
                    'location' => 'Remote',
                    'experience_years' => rand(1, 10),
                ]
            );
            $candidates[] = $candidateUser;
        }

        // 4. Jobs for Employer
        $jobs = [];
        $jobTitles = ['Senior Frontend Engineer', 'Backend Developer', 'Product Manager', 'DevOps Specialist', 'UX Designer'];
        foreach ($jobTitles as $index => $title) {
            $job = JobListing::firstOrCreate(
                [
                    'employer_profile_id' => $employerProfile->id,
                    'title' => $title,
                ],
                [
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'description' => "We are looking for an experienced $title.",
                    'requirements' => '- 5+ years experience\n- Strong communication skills',
                    'benefits' => '- Health insurance\n- 401k match',
                    'location' => 'San Francisco, CA',
                    'salary_min' => 80000 + ($index * 10000),
                    'salary_max' => 120000 + ($index * 10000),
                    'work_type' => 'full-time',
                    'status' => 'approved',
                    'views_count' => rand(50, 500),
                    'deadline' => now()->addDays(30),
                    'approved_at' => now()->subDays(rand(1, 15)),
                ]
            );
            $jobs[] = $job;
        }

        // 5. Applications & Comments
        $statuses = ['pending', 'reviewed', 'interviewed', 'accepted', 'rejected'];
        
        foreach ($jobs as $job) {
            // Add some applications
            $applicantCount = rand(2, 6);
            $jobCandidates = collect($candidates)->random($applicantCount);

            foreach ($jobCandidates as $candidate) {
                Application::firstOrCreate(
                    [
                        'job_listing_id' => $job->id,
                        'candidate_profile_id' => $candidate->candidateProfile->id ?? $candidate->id, // Use profile ID, but fallback just in case
                    ],
                    [
                        'cover_letter' => 'I am very interested in this role.',
                        'status' => $statuses[array_rand($statuses)],
                    ]
                );
            }

            // Add some comments
            $commentCount = rand(1, 3);
            for ($i = 0; $i < $commentCount; $i++) {
                $isReported = rand(1, 10) > 7; // 30% chance of being reported
                $isHidden = $isReported && rand(1, 10) > 5;

                Comment::create([
                    'user_id' => collect($candidates)->random()->id,
                    'job_listing_id' => $job->id,
                    'content' => $isReported ? 'This job looks like a scam!' : 'Great opportunity, applying now!',
                    'is_reported' => $isReported,
                    'is_hidden' => $isHidden,
                ]);
            }
        }
    }
}
