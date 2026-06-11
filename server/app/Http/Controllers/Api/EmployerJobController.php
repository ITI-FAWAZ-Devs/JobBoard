<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobListingRequest;
use App\Http\Requests\UpdateJobListingRequest;
use App\Http\Resources\JobListingResource;
use App\Models\JobListing;

class EmployerJobController extends Controller
{
    public function index()
    {
        $employerProfile = request()->user()->employerProfile;

        if (!$employerProfile) {
            return response()->json(['message' => 'Employer profile not found.'], 404);
        }

        $jobs = JobListing::forEmployer($employerProfile->id)
            ->with(['category'])
            ->withCount(['applications'])
            ->latest()
            ->paginate(15);

        return JobListingResource::collection($jobs);
    }

    public function store(StoreJobListingRequest $request)
    {
        $employerProfile = $request->user()->employerProfile;

        if (!$employerProfile) {
            return response()->json(['message' => 'Employer profile not found.'], 404);
        }

        $job = JobListing::create([
            'employer_profile_id' => $employerProfile->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'requirements' => $request->requirements,
            'benefits' => $request->benefits,
            'location' => $request->location,
            'salary_min' => $request->salary_min,
            'salary_max' => $request->salary_max,
            'work_type' => $request->work_type,
            'deadline' => $request->deadline,
            'status' => 'pending',
        ]);

        $job->load(['employerProfile', 'category']);

        return new JobListingResource($job);
    }

    public function show(JobListing $jobListing)
    {
        $this->authorize('update', $jobListing);

        $jobListing->load(['employerProfile', 'category']);

        return new JobListingResource($jobListing);
    }

    public function update(UpdateJobListingRequest $request, JobListing $jobListing)
    {
        $this->authorize('update', $jobListing);

        $jobListing->update($request->validated());

        $jobListing->load(['employerProfile', 'category']);

        return new JobListingResource($jobListing);
    }

    public function destroy(JobListing $jobListing)
    {
        $this->authorize('delete', $jobListing);

        $jobListing->delete();

        return response()->json(['message' => 'Job listing deleted successfully.']);
    }
}
