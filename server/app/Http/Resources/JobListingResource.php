<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class JobListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'benefits' => $this->benefits,
            'location' => $this->location,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'work_type' => $this->work_type,
            'deadline' => $this->deadline?->format('Y-m-d'),
            'status' => $this->status,
            'views_count' => $this->views_count,
            'applications_count' => $this->when((bool) $this->applications_count, (int) $this->applications_count),
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'employer_profile' => new EmployerProfileResource($this->whenLoaded('employerProfile')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'has_applied' => $this->when(
                Auth::guard('sanctum')->user()?->isCandidate(),
                fn () => $this->has_applied ?? $this->applications()
                    ->where('candidate_profile_id', Auth::guard('sanctum')->user()->candidateProfile?->id)
                    ->exists()
            ),
        ];
    }
}
