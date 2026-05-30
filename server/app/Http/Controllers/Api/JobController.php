<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchJobRequest;
use App\Http\Resources\JobListingResource;
use App\Models\JobListing;

class JobController extends Controller
{
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

        return JobListingResource::collection($query->latest()->paginate($perPage));
    }

    public function show(JobListing $jobListing)
    {
        if ($jobListing->status !== 'approved') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $jobListing->increment('views_count');

        $jobListing->load(['employerProfile', 'category']);

        return new JobListingResource($jobListing);
    }
}
