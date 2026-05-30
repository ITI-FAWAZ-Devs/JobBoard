<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name' => $this->company_name,
            'logo_url' => $this->logo_url,
            'website' => $this->website,
            'phone' => $this->phone,
            'location' => $this->location,
            'description' => $this->description,
        ];
    }
}
