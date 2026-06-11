<?php

namespace App\Events;

use App\Models\JobListing;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public JobListing $job;
    public string $reason;

    public function __construct(JobListing $job, string $reason)
    {
        $this->job = $job;
        $this->reason = $reason;
    }
}
