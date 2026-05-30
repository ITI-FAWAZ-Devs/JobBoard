<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'experiences' => $this->when($this->isCandidate(), function () {
                return $this->experiences->map(fn ($exp) => [
                    'id' => $exp->id,
                    'title' => $exp->title,
                    'company' => $exp->company,
                    'location' => $exp->location,
                    'period' => $exp->period,
                    'description' => $exp->description,
                    'current' => $exp->current,
                ]);
            }, []),
            'education' => $this->when($this->isCandidate(), function () {
                return $this->education->map(fn ($edu) => [
                    'id' => $edu->id,
                    'title' => $edu->title,
                    'school' => $edu->school,
                    'period' => $edu->period,
                ]);
            }, []),
            'profile' => $this->when($this->isEmployer(), function () {
                $profile = $this->employerProfile;

                return $profile ? [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'company_name' => $profile->company_name,
                    'logo' => $profile->logo,
                    'logo_url' => $profile->logo_url,
                    'website' => $profile->website,
                    'phone' => $profile->phone,
                    'location' => $profile->location,
                    'description' => $profile->description,
                    'created_at' => $profile->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $profile->updated_at?->format('Y-m-d H:i:s'),
                ] : null;
            }, $this->when($this->isCandidate(), function () {
                $profile = $this->candidateProfile;

                return $profile ? [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'resume' => $profile->resume,
                    'resume_url' => $profile->resume_url,
                    'phone' => $profile->phone,
                    'linkedin_url' => $profile->linkedin_url,
                    'skills' => $profile->skills,
                    'experience_years' => $profile->experience_years,
                    'location' => $profile->location,
                    'bio' => $profile->bio,
                    'created_at' => $profile->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $profile->updated_at?->format('Y-m-d H:i:s'),
                ] : null;
            }, null)),
        ];
    }
}
