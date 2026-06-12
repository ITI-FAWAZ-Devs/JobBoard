<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerApplicationController extends Controller
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

        $jobIds = JobListing::where('employer_profile_id', $employer->id)
            ->pluck('id');

        $query = Application::whereIn('job_listing_id', $jobIds)
            ->with(['jobListing', 'candidateProfile.user']);

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->query('job_id')) {
            $query->where('job_listing_id', $request->query('job_id'));
        }

        $applications = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Applications retrieved successfully.',
            'data' => [
                'data' => $applications->map(fn ($app) => $this->formatApplication($app)),
                'meta' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                ],
            ],
        ]);
    }

    public function accept(Request $request, Application $application): JsonResponse
    {
        $employer = $request->user()->employerProfile;

        if (! $employer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $job = $application->jobListing;

        if (! $job || $job->employer_profile_id !== $employer->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to manage this application.',
                'data' => null,
            ], 403);
        }

        $application->update(['status' => 'accepted']);

        return response()->json([
            'status' => 'success',
            'message' => 'Application accepted.',
            'data' => $this->formatApplication($application->fresh(['jobListing', 'candidateProfile.user'])),
        ]);
    }

    public function reject(Request $request, Application $application): JsonResponse
    {
        $employer = $request->user()->employerProfile;

        if (! $employer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $job = $application->jobListing;

        if (! $job || $job->employer_profile_id !== $employer->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to manage this application.',
                'data' => null,
            ], 403);
        }

        $application->update(['status' => 'rejected']);

        return response()->json([
            'status' => 'success',
            'message' => 'Application rejected.',
            'data' => $this->formatApplication($application->fresh(['jobListing', 'candidateProfile.user'])),
        ]);
    }

    private function formatApplication(Application $application): array
    {
        $candidate = $application->candidateProfile?->user;
        $job = $application->jobListing;
        $isPaid = $application->status === 'paid';

        return [
            'id' => $application->id,
            'status' => $application->status,
            'cover_letter' => $application->cover_letter,
            'created_at' => $application->created_at?->format('Y-m-d H:i:s'),
            'candidate' => $candidate ? [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $isPaid ? $candidate->email : null,
                'avatar_url' => $candidate->avatar_url,
                'profile' => [
                    'location' => $application->candidateProfile?->location,
                    'experience_years' => $application->candidateProfile?->experience_years,
                    'skills' => $application->candidateProfile?->skills,
                    'phone' => $isPaid ? $application->candidateProfile?->phone : null,
                    'resume_url' => $isPaid
                        ? ($application->resume_url ?? $application->candidateProfile?->resume_url)
                        : null,
                ],
            ] : null,
            'job' => $job ? [
                'id' => $job->id,
                'title' => $job->title,
                'location' => $job->location,
                'work_type' => $job->work_type,
            ] : null,
        ];
    }
}
