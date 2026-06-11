<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerCandidateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 12);

        $candidates = User::query()
            ->where('role', 'candidate')
            ->with('candidateProfile')
            ->latest()
            ->paginate($perPage);

        $payload = $candidates->through(function (User $user) {
            $profile = $user->candidateProfile;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role,
                'profile' => $profile ? [
                    'location' => $profile->location,
                    'experience_years' => $profile->experience_years,
                    'skills' => $profile->skills,
                    'bio' => $profile->bio,
                ] : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Candidates fetched successfully.',
            'data' => [
                'data' => $payload->items(),
                'meta' => [
                    'current_page' => $candidates->currentPage(),
                    'last_page' => $candidates->lastPage(),
                    'per_page' => $candidates->perPage(),
                    'total' => $candidates->total(),
                ],
            ],
        ]);
    }

    public function contact(Request $request, User $candidate): JsonResponse
    {
        $user = $request->user();

        if ($candidate->role !== 'candidate') {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate not found.',
                'data' => null,
            ], 404);
        }

        $jobId = $request->integer('job_id');

        if (! $jobId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job id is required.',
                'data' => null,
            ], 422);
        }

        $employerProfile = $user->employerProfile;

        if (! $employerProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $job = JobListing::query()
            ->where('id', $jobId)
            ->where('employer_profile_id', $employerProfile->id)
            ->first();

        if (! $job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found for this employer.',
                'data' => null,
            ], 404);
        }

        $paid = Payment::query()
            ->where('employer_id', $user->id)
            ->where('candidate_id', $candidate->id)
            ->where('job_id', $job->id)
            ->where('status', 'paid')
            ->exists();

        if (! $paid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment required to reveal contact details.',
                'data' => null,
            ], 403);
        }

        $profile = $candidate->candidateProfile;

        return response()->json([
            'status' => 'success',
            'message' => 'Contact details unlocked.',
            'data' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $profile?->phone,
                'linkedin_url' => $profile?->linkedin_url,
                'resume_url' => $profile?->resume_url,
            ],
        ]);
    }
}
