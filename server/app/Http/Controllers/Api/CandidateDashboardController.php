<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\SavedJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCandidate()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only candidates can view the candidate dashboard.',
                'data' => null,
            ], 403);
        }

        $candidateProfile = $user->candidateProfile;

        // Statistics
        $appliedCount = 0;
        $savedCount = SavedJob::where('user_id', $user->id)->count();
        $profileCompletePercent = 20; // base value for account creation

        if ($candidateProfile) {
            $appliedCount = Application::where('candidate_profile_id', $candidateProfile->id)->count();

            // Calculate profile completeness
            if (!empty($candidateProfile->bio)) $profileCompletePercent += 15;
            if (!empty($candidateProfile->resume)) $profileCompletePercent += 15;
            if (!empty($candidateProfile->phone)) $profileCompletePercent += 10;
            if (!empty($candidateProfile->linkedin_url)) $profileCompletePercent += 10;
            if (!empty($candidateProfile->skills)) $profileCompletePercent += 15;
            if (!empty($candidateProfile->experience_years)) $profileCompletePercent += 10;
            if (!empty($candidateProfile->location)) $profileCompletePercent += 5;
        }

        // Add extra completeness for experiences or education
        if ($user->experiences()->exists()) {
            $profileCompletePercent = min(100, $profileCompletePercent + 15);
        }

        // Recent Applications (latest 5)
        $recentApplications = [];
        if ($candidateProfile) {
            $recentApplications = Application::where('candidate_profile_id', $candidateProfile->id)
                ->with(['jobListing.employerProfile'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($app) => [
                    'id' => $app->id,
                    'status' => $app->status,
                    'created_at' => $app->created_at?->diffForHumans(),
                    'job' => $app->jobListing ? [
                        'id' => $app->jobListing->id,
                        'title' => $app->jobListing->title,
                        'company_name' => $app->jobListing->employerProfile?->company_name,
                        'location' => $app->jobListing->location,
                    ] : null,
                ])->filter(fn ($item) => $item['job'] !== null)->values();
        }

        // Recent Saved Jobs (latest 4)
        $recentSavedJobs = SavedJob::where('user_id', $user->id)
            ->with(['jobListing.employerProfile', 'jobListing.category'])
            ->latest()
            ->take(4)
            ->get()
            ->map(fn ($saved) => [
                'id' => $saved->id,
                'job_listing_id' => $saved->job_listing_id,
                'job' => $saved->jobListing ? [
                    'id' => $saved->jobListing->id,
                    'title' => $saved->jobListing->title,
                    'company_name' => $saved->jobListing->employerProfile?->company_name,
                    'location' => $saved->jobListing->location,
                    'work_type' => $saved->jobListing->work_type,
                    'salary_min' => $saved->jobListing->salary_min,
                    'salary_max' => $saved->jobListing->salary_max,
                    'category' => $saved->jobListing->category?->name,
                ] : null,
            ])->filter(fn ($item) => $item['job'] !== null)->values();

        // Recommended Jobs (latest 4 active jobs)
        $recommendedJobs = JobListing::approved()
            ->with(['employerProfile', 'category'])
            ->latest()
            ->take(4)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'company_name' => $job->employerProfile?->company_name,
                'location' => $job->location,
                'work_type' => $job->work_type,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'category' => $job->category?->name,
            ]);

        // Recent Activity (latest 5 notifications)
        $recentActivity = $user->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'data' => $n->data,
                'read_at' => $n->read_at?->format('Y-m-d H:i:s'),
                'created_at' => $n->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Candidate dashboard details retrieved.',
            'data' => [
                'stats' => [
                    'applied_count' => $appliedCount,
                    'saved_count' => $savedCount,
                    'profile_complete_percent' => min(100, $profileCompletePercent),
                ],
                'recent_applications' => $recentApplications,
                'saved_jobs' => $recentSavedJobs,
                'recommended_jobs' => $recommendedJobs,
                'activity' => $recentActivity,
            ],
        ]);
    }
}
