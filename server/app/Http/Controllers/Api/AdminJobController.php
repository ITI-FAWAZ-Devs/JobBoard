<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectJobRequest;
use App\Http\Resources\JobListingResource;
use App\Models\JobListing;

class AdminJobController extends Controller
{
    public function pending()
    {
        $this->authorize('viewAny', JobListing::class);

        $jobs = JobListing::pending()
            ->with(['employerProfile', 'category'])
            ->latest()
            ->paginate(15);

        return JobListingResource::collection($jobs);
    }

    public function approve(JobListing $jobListing)
    {
        $this->authorize('viewAny', JobListing::class);

        if ($jobListing->status !== 'pending') {
            return response()->json(['message' => 'Job listing is not pending approval.'], 422);
        }

        $jobListing->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $jobListing->load(['employerProfile', 'category']);

        return new JobListingResource($jobListing);
    }

    public function reject(RejectJobRequest $request, JobListing $jobListing)
    {
        $this->authorize('viewAny', JobListing::class);

        if ($jobListing->status !== 'pending') {
            return response()->json(['message' => 'Job listing is not pending approval.'], 422);
        }

        $jobListing->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        $jobListing->load(['employerProfile', 'category']);

        return new JobListingResource($jobListing);
    }
}
