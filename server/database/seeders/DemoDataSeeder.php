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

        // 2. Diverse Employers & Company Profiles
        $companiesData = [
            [
                'email' => 'employer@demo.com',
                'name' => 'TechCorp HR',
                'company_name' => 'TechCorp Global',
                'website' => 'https://techcorp.example.com',
                'location' => 'Remote',
                'industry' => 'Programming',
                'employee_count' => '201-500 Employees',
                'description' => 'We build the tools that empower remote teams to collaborate seamlessly across timezones. Join our mission to redefine work.',
            ],
            [
                'email' => 'nova@demo.com',
                'name' => 'Nova HR',
                'company_name' => 'Nova Design Studio',
                'website' => 'https://novadesign.example.com',
                'location' => 'Cairo, Egypt',
                'industry' => 'Design',
                'employee_count' => '11-50 Employees',
                'description' => 'Award-winning digital experiences. We value creativity, work-life balance, and pushing boundaries in interactive design.',
            ],
            [
                'email' => 'fintrust@demo.com',
                'name' => 'FinTrust HR',
                'company_name' => 'FinTrust Partners',
                'website' => 'https://fintrust.example.com',
                'location' => 'New York, NY',
                'industry' => 'Finance',
                'employee_count' => '501+ Employees',
                'description' => 'Leading the future of ethical banking and sustainable investments. Join a team dedicated to financial empowerment.',
            ],
            [
                'email' => 'acme@demo.com',
                'name' => 'Acme HR',
                'company_name' => 'Acme Corp',
                'website' => 'https://acme.example.com',
                'location' => 'Remote',
                'industry' => 'Programming',
                'employee_count' => '201-500 Employees',
                'description' => 'E-Commerce platforms for modern enterprise markets and custom retail experiences worldwide.',
            ],
            [
                'email' => 'greenlife@demo.com',
                'name' => 'GreenLife HR',
                'company_name' => 'GreenLife Logistics',
                'website' => 'https://greenlife.example.com',
                'location' => 'Chicago, IL',
                'industry' => 'Sales',
                'employee_count' => '51-200 Employees',
                'description' => 'Eco-friendly and carbon-neutral supply chain solutions for consumer businesses across North America.',
            ],
            [
                'email' => 'starlight@demo.com',
                'name' => 'Starlight HR',
                'company_name' => 'Starlight Media',
                'website' => 'https://starlight.example.com',
                'location' => 'New York, NY',
                'industry' => 'Marketing',
                'employee_count' => '11-50 Employees',
                'description' => 'SaaS marketing solutions and organic outreach strategies for venture-funded brands.',
            ]
        ];

        $employerProfiles = [];
        $employerUser = null; // Keep a reference to first employer for applications consistency

        foreach ($companiesData as $c) {
            $user = User::firstOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'password' => Hash::make('password'),
                    'role' => 'employer',
                ]
            );

            if ($c['email'] === 'employer@demo.com') {
                $employerUser = $user;
            }

            $profile = EmployerProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $c['company_name'],
                    'website' => $c['website'],
                    'location' => $c['location'],
                    'industry' => $c['industry'],
                    'employee_count' => $c['employee_count'],
                    'description' => $c['description'],
                ]
            );

            $employerProfiles[] = $profile;
        }

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
        
        // Seed multiple jobs for the first employer (employer@demo.com)
        $firstEmployerId = $employerProfiles[0]->id;
        $firstEmployerJobs = [
            [
                'title' => 'Senior Frontend Engineer',
                'status' => 'approved',
                'views_count' => 380,
                'salary_min' => 90000,
                'salary_max' => 140000,
            ],
            [
                'title' => 'Senior Laravel Architect',
                'status' => 'approved',
                'views_count' => 240,
                'salary_min' => 100000,
                'salary_max' => 160000,
            ],
            [
                'title' => 'Junior QA Engineer',
                'status' => 'pending',
                'views_count' => 15,
                'salary_min' => 40000,
                'salary_max' => 60000,
            ],
            [
                'title' => 'UI/UX Designer (Mobile)',
                'status' => 'rejected',
                'views_count' => 0,
                'salary_min' => 50000,
                'salary_max' => 80000,
            ],
        ];

        foreach ($firstEmployerJobs as $item) {
            $job = JobListing::firstOrCreate(
                [
                    'employer_profile_id' => $firstEmployerId,
                    'title' => $item['title'],
                ],
                [
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'description' => "We are looking for an experienced " . $item['title'] . ".",
                    'requirements' => "- 3+ years experience\n- Strong technical and collaborative skills",
                    'benefits' => "- Medical insurance\n- Flexible working hours",
                    'location' => 'Remote',
                    'salary_min' => $item['salary_min'],
                    'salary_max' => $item['salary_max'],
                    'work_type' => 'full-time',
                    'status' => $item['status'],
                    'views_count' => $item['views_count'],
                    'deadline' => now()->addDays(30),
                    'approved_at' => $item['status'] === 'approved' ? now()->subDays(5) : null,
                ]
            );
            $jobs[] = $job;

            // Seed JobViews for the job if not already present
            if ($item['views_count'] > 0 && \App\Models\JobView::where('job_listing_id', $job->id)->count() === 0) {
                for ($v = 0; $v < $item['views_count']; $v++) {
                    \App\Models\JobView::create([
                        'job_listing_id' => $job->id,
                        'viewed_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Mozilla/5.0',
                    ]);
                }
            }
        }

        // Seed one job for the other employers
        $otherJobs = [
            ['title' => 'Backend Developer', 'employer_index' => 1],
            ['title' => 'Product Manager', 'employer_index' => 2],
            ['title' => 'DevOps Specialist', 'employer_index' => 3],
            ['title' => 'UX Designer', 'employer_index' => 4],
            ['title' => 'Sales Lead', 'employer_index' => 5],
        ];

        foreach ($otherJobs as $index => $item) {
            $job = JobListing::firstOrCreate(
                [
                    'employer_profile_id' => $employerProfiles[$item['employer_index']]->id,
                    'title' => $item['title'],
                ],
                [
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'description' => "We are looking for an experienced " . $item['title'] . ".",
                    'requirements' => "- 5+ years experience\n- Excellent skills",
                    'benefits' => "- Health insurance\n- 401k match",
                    'location' => $employerProfiles[$item['employer_index']]->location ?? 'Remote',
                    'salary_min' => 70000 + ($index * 10000),
                    'salary_max' => 110000 + ($index * 10000),
                    'work_type' => 'full-time',
                    'status' => 'approved',
                    'views_count' => rand(50, 300),
                    'deadline' => now()->addDays(30),
                    'approved_at' => now()->subDays(rand(1, 15)),
                ]
            );
            $jobs[] = $job;

            // Seed JobViews for the job if not already present
            if (\App\Models\JobView::where('job_listing_id', $job->id)->count() === 0) {
                $viewsToSeed = $job->views_count;
                for ($v = 0; $v < $viewsToSeed; $v++) {
                    \App\Models\JobView::create([
                        'job_listing_id' => $job->id,
                        'viewed_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Mozilla/5.0',
                    ]);
                }
            }
        }



        // 5. Applications & Comments
        $statuses = ['pending', 'reviewed', 'interviewed', 'accepted', 'rejected', 'paid'];
        
        foreach ($jobs as $job) {
            // Add some applications
            $applicantCount = rand(2, 6);
            $jobCandidates = collect($candidates)->random($applicantCount);

            foreach ($jobCandidates as $candidate) {
                $status = $statuses[array_rand($statuses)];

                $app = Application::firstOrCreate(
                    [
                        'job_listing_id' => $job->id,
                        'candidate_profile_id' => $candidate->candidateProfile->id ?? $candidate->id, // Use profile ID, but fallback just in case
                    ],
                    [
                        'cover_letter' => 'I am very interested in this role.',
                        'status' => $status,
                    ]
                );

                if ($status === 'paid') {
                    \App\Models\Payment::firstOrCreate(
                        [
                            'application_id' => $app->id,
                        ],
                        [
                            'employer_id' => $employerUser->id,
                            'candidate_id' => $candidate->id,
                            'job_id' => $job->id,
                            'provider' => 'stripe',
                            'amount' => 49.00,
                            'currency' => 'usd',
                            'status' => 'paid',
                            'stripe_payment_intent_id' => 'pi_fake_' . \Illuminate\Support\Str::random(16),
                            'paid_at' => now(),
                        ]
                    );
                }
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

        // 6. Seed Notifications for Testing
        $candidate1 = User::where('email', 'candidate1@demo.com')->first();
        if ($candidate1) {
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                [
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'App\Notifications\JobStatusChanged',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $candidate1->id,
                    'data' => json_encode([
                        'job_id' => 1,
                        'job_title' => 'Senior Frontend Engineer',
                        'status' => 'approved',
                        'reason' => null,
                    ]),
                    'read_at' => null,
                    'created_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
                    'updated_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'App\Notifications\UserStatusChanged',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $candidate1->id,
                    'data' => json_encode([
                        'user_id' => $candidate1->id,
                        'status' => 'active',
                    ]),
                    'read_at' => null,
                    'created_at' => now()->subDays(1)->format('Y-m-d H:i:s'),
                    'updated_at' => now()->subDays(1)->format('Y-m-d H:i:s'),
                ]
            ]);

            // 7. Seed Saved Jobs for Candidate1
            $savedJobIds = JobListing::approved()->take(3)->pluck('id');
            foreach ($savedJobIds as $jobId) {
                \App\Models\SavedJob::firstOrCreate([
                    'user_id' => $candidate1->id,
                    'job_listing_id' => $jobId,
                ]);
            }
        }

        if ($employerUser) {
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                [
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'App\Notifications\JobStatusChanged',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $employerUser->id,
                    'data' => json_encode([
                        'job_id' => 2,
                        'job_title' => 'Backend Developer',
                        'status' => 'approved',
                        'reason' => null,
                    ]),
                    'read_at' => null,
                    'created_at' => now()->subHours(3)->format('Y-m-d H:i:s'),
                    'updated_at' => now()->subHours(3)->format('Y-m-d H:i:s'),
                ]
            ]);
        }

        // 8. Seed Salary Reports
        $salaryReports = [
            [
                'title' => 'Senior Frontend Engineer',
                'location' => 'Remote',
                'level' => 'Senior Level',
                'category' => 'Engineering',
                'median_salary' => 85000,
                'min_salary' => 60000,
                'max_salary' => 110000,
                'report_count' => 142,
                'currency' => 'EGP',
            ],
            [
                'title' => 'Product Manager',
                'location' => 'Cairo, Egypt',
                'level' => 'Mid Level',
                'category' => 'Product',
                'median_salary' => 65000,
                'min_salary' => 45000,
                'max_salary' => 90000,
                'report_count' => 89,
                'currency' => 'EGP',
            ],
            [
                'title' => 'UI/UX Designer',
                'location' => 'Cairo, Egypt',
                'level' => 'Senior Level',
                'category' => 'Design',
                'median_salary' => 55000,
                'min_salary' => 40000,
                'max_salary' => 75000,
                'report_count' => 74,
                'currency' => 'EGP',
            ],
            [
                'title' => 'Data Analyst',
                'location' => 'Alexandria, Egypt',
                'level' => 'Mid Level',
                'category' => 'Engineering',
                'median_salary' => 35000,
                'min_salary' => 25000,
                'max_salary' => 50000,
                'report_count' => 62,
                'currency' => 'EGP',
            ],
            [
                'title' => 'Marketing Specialist',
                'location' => 'Remote',
                'level' => 'Entry Level',
                'category' => 'Marketing',
                'median_salary' => 20000,
                'min_salary' => 15000,
                'max_salary' => 28000,
                'report_count' => 45,
                'currency' => 'EGP',
            ],
            [
                'title' => 'Software Engineer',
                'location' => 'Cairo, Egypt',
                'level' => 'Mid Level',
                'category' => 'Engineering',
                'median_salary' => 45000,
                'min_salary' => 32000,
                'max_salary' => 60000,
                'report_count' => 198,
                'currency' => 'EGP',
            ]
        ];

        foreach ($salaryReports as $rep) {
            \App\Models\SalaryReport::firstOrCreate(
                [
                    'title' => $rep['title'],
                    'level' => $rep['level'],
                    'location' => $rep['location']
                ],
                $rep
            );
        }
    }
}
