<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\JobView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmployerAnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employer = $request->user()->employerProfile;

        if (! $employer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $employerId = $employer->id;

        $data = Cache::remember("employer_analytics_{$employerId}", 300, function () use ($employerId) {
            $jobs = JobListing::where('employer_profile_id', $employerId)
                ->withCount(['applications as paid_applications' => function ($q) {
                    $q->where('status', 'paid');
                }])
                ->withCount('applications')
                ->orderBy('created_at', 'desc')
                ->get();

            $jobIds = $jobs->pluck('id');

            $totalViews = JobView::whereIn('job_listing_id', $jobIds)->count();

            $totalApplicants = $jobs->sum('applications_count');

            $paidApplications = Application::whereIn('job_listing_id', $jobIds)
                ->where('status', 'paid')
                ->count();

            $conversionRate = $totalViews > 0
                ? round($paidApplications / $totalViews, 4)
                : 0;

            $perListing = $jobs->map(function ($job) {
                return [
                    'title' => $job->title,
                    'views' => (int) JobView::where('job_listing_id', $job->id)->count(),
                    'applicants' => (int) $job->applications_count,
                ];
            });

            $viewsOverTime = JobView::whereIn('job_listing_id', $jobIds)
                ->where('viewed_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(viewed_at) as date, COUNT(*) as views')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($v) => ['date' => $v->date, 'views' => (int) $v->views]);

            return [
                'views' => $totalViews,
                'applicants' => $totalApplicants,
                'conversion_rate' => $conversionRate,
                'per_listing' => $perListing,
                'views_over_time' => $viewsOverTime,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Analytics retrieved successfully.',
            'data' => $data,
        ]);
    }
}
