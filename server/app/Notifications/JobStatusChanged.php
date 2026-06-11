<?php

namespace App\Notifications;

use App\Models\JobListing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobStatusChanged extends Notification
{
    use Queueable;

    public JobListing $job;
    public string $status;
    public ?string $reason;

    public function __construct(JobListing $job, string $status, ?string $reason = null)
    {
        $this->job = $job;
        $this->status = $status;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Job \"{$this->job->title}\" {$this->status}")
            ->line("Your job listing \"{$this->job->title}\" has been {$this->status}.");

        if ($this->reason) {
            $message->line("Reason: {$this->reason}");
        }

        return $message
            ->action('View Job', url("/jobs/{$this->job->id}"))
            ->line('Thank you for using WorkHive!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'job_id' => $this->job->id,
            'job_title' => $this->job->title,
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}
