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
        $status = 'active';

        if ($this->banned_at) {
            $status = 'banned';
        } elseif ($this->suspended_at) {
            $status = 'suspended';
        } elseif (! $this->is_active) {
            $status = 'inactive';
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'status' => $status,
            'suspended_at' => $this->suspended_at?->format('Y-m-d H:i:s'),
            'banned_at' => $this->banned_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
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
