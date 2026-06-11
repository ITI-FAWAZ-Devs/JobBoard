<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserStatusChanged extends Notification
{
    use Queueable;

    public User $targetUser;
    public string $status;

    public function __construct(User $targetUser, string $status)
    {
        $this->targetUser = $targetUser;
        $this->status = $status;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Account {$this->status}")
            ->line("Your account has been {$this->status}.")
            ->line('If you believe this is a mistake, please contact support.')
            ->line('Thank you for using WorkHive!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->targetUser->id,
            'status' => $this->status,
        ];
    }
}
