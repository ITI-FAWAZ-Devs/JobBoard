<?php

namespace App\Policies;

use App\Models\JobListing;
use App\Models\User;

class JobListingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(?User $user, JobListing $jobListing): bool
    {
        return $jobListing->status === 'approved';
    }

    public function create(User $user): bool
    {
        return $user->isEmployer();
    }

    public function update(User $user, JobListing $jobListing): bool
    {
        return $user->isEmployer()
            && $user->employerProfile
            && $user->employerProfile->id === $jobListing->employer_profile_id;
    }

    public function delete(User $user, JobListing $jobListing): bool
    {
        return $this->update($user, $jobListing);
    }

    public function approve(User $user, JobListing $jobListing): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, JobListing $jobListing): bool
    {
        return $user->isAdmin();
    }
}
