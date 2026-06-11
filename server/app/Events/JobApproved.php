<?php

namespace App\Events;

use App\Models\JobListing;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public JobListing $job;

    public function __construct(JobListing $job)
    {
        $this->job = $job;
    }
}
