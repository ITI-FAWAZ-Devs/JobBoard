<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchJobRequest;
use App\Http\Resources\JobListingResource;
use App\Models\JobListing;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    private function applyHasAppliedToQuery($query): void
    {
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->isCandidate() && $user->candidateProfile) {
            $candidateProfileId = $user->candidateProfile->id;
            $query->withExists(['applications as has_applied' => function ($q) use ($candidateProfileId) {
                $q->where('candidate_profile_id', $candidateProfileId);
            }]);
        }
    }

    public function index(SearchJobRequest $request)
    {
        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 15;

        if (!empty($validated['q'])) {
            $jobIds = JobListing::search($validated['q'])
                ->where('status', 'approved')
                ->get()
                ->pluck('id');

            $query = JobListing::approved()
                ->with(['employerProfile', 'category'])
                ->whereIn('id', $jobIds);
        } else {
            $query = JobListing::approved()->with(['employerProfile', 'category']);
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (!empty($validated['location'])) {
            $query->where('location', 'like', '%' . $validated['location'] . '%');
        }

        if (!empty($validated['work_type'])) {
            $query->where('work_type', $validated['work_type']);
        }

        if (!empty($validated['salary_min'])) {
            $query->where('salary_max', '>=', $validated['salary_min']);
        }

        if (!empty($validated['salary_max'])) {
            $query->where('salary_min', '<=', $validated['salary_max']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $this->applyHasAppliedToQuery($query);

        return JobListingResource::collection($query->latest()->paginate($perPage));
    }

    public function show(JobListing $jobListing)
    {
        if ($jobListing->status !== 'approved') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $jobListing->increment('views_count');

        try {
            \App\Models\JobView::create([
                'job_listing_id' => $jobListing->id,
                'user_id' => Auth::guard('sanctum')->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'viewed_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if there's any issue with tracking to not block showing the job
        }

        $jobListing->load(['employerProfile', 'category']);

        $user = Auth::guard('sanctum')->user();
        if ($user && $user->isCandidate() && $user->candidateProfile) {
            $jobListing->has_applied = $jobListing->applications()
                ->where('candidate_profile_id', $user->candidateProfile->id)
                ->exists();
        }

        return new JobListingResource($jobListing);
    }

    public function statistics()
    {
        $jobsCount = JobListing::where('status', 'approved')->count();
        $candidatesCount = \App\Models\User::where('role', 'candidate')->count();
        $companiesCount = \App\Models\EmployerProfile::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'jobs_count' => $jobsCount,
                'candidates_count' => $candidatesCount,
                'companies_count' => $companiesCount,
            ]
        ]);
    }
}
