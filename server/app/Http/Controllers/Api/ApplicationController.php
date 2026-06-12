<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function store(Request $request, JobListing $jobListing): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCandidate()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only candidates can apply for jobs.',
                'data' => null,
            ], 403);
        }

        if ($jobListing->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'This job is not currently accepting applications.',
                'data' => null,
            ], 422);
        }

        $candidateProfile = $user->candidateProfile;

        if (! $candidateProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate profile not found.',
                'data' => null,
            ], 404);
        }

        $exists = Application::where('job_listing_id', $jobListing->id)
            ->where('candidate_profile_id', $candidateProfile->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already applied for this job.',
                'data' => null,
            ], 409);
        }

        $validated = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'resume' => ['nullable', 'file', 'mimes:pdf,docx,doc', 'max:5120'],
        ]);

        $resumePath = $request->hasFile('resume')
            ? $request->file('resume')->store('resumes', 'public')
            : $candidateProfile->resume;

        $application = Application::create([
            'job_listing_id' => $jobListing->id,
            'candidate_profile_id' => $candidateProfile->id,
            'cover_letter' => $validated['cover_letter'] ?? null,
            'resume_path' => $resumePath,
            'status' => 'pending',
        ]);

        $application->load(['jobListing', 'candidateProfile.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
            'data' => $this->formatApplication($application),
        ], 201);
    }

    /**
     * One-click apply using the resume already stored on the candidate profile.
     */
    public function quickApply(Request $request, JobListing $jobListing): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCandidate()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only candidates can apply for jobs.',
                'data' => null,
            ], 403);
        }

        if ($jobListing->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'This job is not currently accepting applications.',
                'data' => null,
            ], 422);
        }

        $candidateProfile = $user->candidateProfile;

        if (! $candidateProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate profile not found.',
                'data' => null,
            ], 404);
        }

        if (! $candidateProfile->resume) {
            return response()->json([
                'status' => 'error',
                'message' => 'Upload a resume to your profile to use Quick Apply.',
                'code' => 'resume_required',
                'data' => null,
            ], 422);
        }

        $exists = Application::where('job_listing_id', $jobListing->id)
            ->where('candidate_profile_id', $candidateProfile->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already applied for this job.',
                'data' => null,
            ], 409);
        }

        $application = Application::create([
            'job_listing_id' => $jobListing->id,
            'candidate_profile_id' => $candidateProfile->id,
            'cover_letter' => null,
            'resume_path' => $candidateProfile->resume,
            'status' => 'pending',
        ]);

        $application->load(['jobListing', 'candidateProfile.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted with your profile resume.',
            'data' => $this->formatApplication($application),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCandidate()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only candidates can view their applications.',
                'data' => null,
            ], 403);
        }

        $candidateProfile = $user->candidateProfile;

        if (! $candidateProfile) {
            return response()->json([
                'status' => 'success',
                'message' => 'No applications found.',
                'data' => ['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0]],
            ]);
        }

        $applications = Application::where('candidate_profile_id', $candidateProfile->id)
            ->with(['jobListing.employerProfile', 'jobListing.category'])
            ->latest()
            ->paginate(15);

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

    public function destroy(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();
        $candidateProfile = $user->candidateProfile;

        if (! $candidateProfile || $application->candidate_profile_id !== $candidateProfile->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to cancel this application.',
                'data' => null,
            ], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending applications can be cancelled.',
                'data' => null,
            ], 422);
        }

        $application->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Application cancelled successfully.',
            'data' => null,
        ]);
    }

    private function formatApplication(Application $application): array
    {
        $job = $application->jobListing;

        return [
            'id' => $application->id,
            'status' => $application->status,
            'cover_letter' => $application->cover_letter,
            'created_at' => $application->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $application->updated_at?->format('Y-m-d H:i:s'),
            'job' => $job ? [
                'id' => $job->id,
                'title' => $job->title,
                'location' => $job->location,
                'work_type' => $job->work_type,
                'company_name' => $job->employerProfile?->company_name,
                'category' => $job->category?->name,
            ] : null,
        ];
    }
}
