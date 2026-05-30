<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployerAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $employer = $request->user()->employerProfile;
        
        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found', 'status' => 'error'], 404);
        }

        $jobs = \App\Models\JobListing::where('employer_profile_id', $employer->id)
            ->withCount('applications')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($job) {
                $job->conversion_rate = $job->views_count > 0 
                    ? round(($job->applications_count / $job->views_count) * 100, 1) 
                    : 0;
                return $job;
            });

        $totalViews = $jobs->sum('views_count');
        $totalApplications = $jobs->sum('applications_count');
        
        $interviewedApplications = \App\Models\Application::whereIn('job_listing_id', $jobs->pluck('id'))
            ->whereIn('status', ['interviewed', 'accepted'])
            ->count();

        $interviewConversion = $totalApplications > 0 
            ? round(($interviewedApplications / $totalApplications) * 100, 1) 
            : 0;

        return response()->json([
            'status' => 'success',
            'message' => 'Analytics retrieved successfully',
            'data' => [
                'total_profile_views' => $totalViews,
                'total_applications' => $totalApplications,
                'interview_conversion' => $interviewConversion,
                'jobs' => $jobs
            ]
        ]);
    }
}
