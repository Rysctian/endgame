<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification
{
    public function __construct(public WorkspaceInvitation $invitation, public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Invitation to '.$this->invitation->workspace->name)
            ->line('You have been invited to '.$this->invitation->workspace->name.'.')
            ->line('Sign in with '.$this->invitation->email.', verify your email, then accept. This invitation expires in seven days.')
            ->action('Review invitation', route('invitations.show', [$this->invitation, $this->token]));
    }
}
