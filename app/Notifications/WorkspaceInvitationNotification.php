<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're invited to {$this->invitation->workspace->name}")
            ->greeting('Welcome to LeadFlow AI CRM')
            ->line("You were invited as {$this->invitation->role}.")
            ->action('Accept invitation', route('invitations.accept', $this->invitation))
            ->line('Create or log in to your account with this email address to join the workspace.');
    }
}
