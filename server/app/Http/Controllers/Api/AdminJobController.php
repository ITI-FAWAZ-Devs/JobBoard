<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectJobRequest;
use App\Http\Resources\JobListingResource;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;

class AdminJobController extends Controller
{
    public function pending(): JsonResponse
    {
        $this->authorize('viewAny', JobListing::class);

        $jobs = JobListing::pending()
            ->with(['employerProfile', 'category'])
            ->latest()
            ->paginate(15);

        $payload = JobListingResource::collection($jobs)->response()->getData(true);

        return response()->json([
            'status' => 'success',
            'message' => 'Pending jobs fetched successfully.',
            'data' => $payload,
        ]);
    }

    public function approve(JobListing $jobListing): JsonResponse
    {
        $this->authorize('approve', $jobListing);

        if ($jobListing->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job listing is not pending approval.',
                'data' => null,
            ], 422);
        }

        $jobListing->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $jobListing->load(['employerProfile', 'category']);

        return response()->json([
            'status' => 'success',
            'message' => 'Job listing approved successfully.',
            'data' => (new JobListingResource($jobListing))->resolve(),
        ]);
    }

    public function reject(RejectJobRequest $request, JobListing $jobListing): JsonResponse
    {
        $this->authorize('reject', $jobListing);

        if ($jobListing->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job listing is not pending approval.',
                'data' => null,
            ], 422);
        }

        $jobListing->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        $jobListing->load(['employerProfile', 'category']);

        return response()->json([
            'status' => 'success',
            'message' => 'Job listing rejected successfully.',
            'data' => (new JobListingResource($jobListing))->resolve(),
        ]);
    }
}
