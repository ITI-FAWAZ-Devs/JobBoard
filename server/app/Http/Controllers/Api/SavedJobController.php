<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\SavedJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCandidate()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only candidates can have saved jobs.',
                'data' => null,
            ], 403);
        }

        $savedJobs = SavedJob::where('user_id', $user->id)
            ->with(['jobListing.employerProfile', 'jobListing.category'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Saved jobs retrieved successfully.',
            'data' => [
                'data' => $savedJobs->map(fn ($saved) => [
                    'id' => $saved->id,
                    'saved_at' => $saved->created_at?->diffForHumans(),
                    'job' => $saved->jobListing ? [
                        'id' => $saved->jobListing->id,
                        'title' => $saved->jobListing->title,
                        'description' => $saved->jobListing->description,
                        'location' => $saved->jobListing->location,
                        'work_type' => $saved->jobListing->work_type,
                        'salary_min' => $saved->jobListing->salary_min,
                        'salary_max' => $saved->jobListing->salary_max,
                        'company_name' => $saved->jobListing->employerProfile?->company_name,
                        'category' => $saved->jobListing->category?->name,
                    ] : null,
                ])->filter(fn ($item) => $item['job'] !== null)->values(),
                'meta' => [
                    'current_page' => $savedJobs->currentPage(),
                    'last_page' => $savedJobs->lastPage(),
                    'per_page' => $savedJobs->perPage(),
                    'total' => $savedJobs->total(),
                ],
            ],
        ]);
    }

    public function store(Request $request, JobListing $jobListing): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCandidate()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only candidates can save jobs.',
                'data' => null,
            ], 403);
        }

        $saved = SavedJob::firstOrCreate([
            'user_id' => $user->id,
            'job_listing_id' => $jobListing->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Job saved successfully.',
            'data' => [
                'id' => $saved->id,
                'job_listing_id' => $saved->job_listing_id,
            ],
        ]);
    }

    public function destroy(Request $request, JobListing $jobListing): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCandidate()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only candidates can unsave jobs.',
                'data' => null,
            ], 403);
        }

        SavedJob::where('user_id', $user->id)
            ->where('job_listing_id', $jobListing->id)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Job removed from saved list.',
            'data' => null,
        ]);
    }
}
