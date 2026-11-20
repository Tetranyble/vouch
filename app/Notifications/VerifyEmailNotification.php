<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    /**
     * Build Vouch's account verification email without changing Laravel's
     * signed URL or expiry behaviour.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your Vouch email address')
            ->view('mail.verify-email', [
                'name' => $notifiable->getDisplayNameAttribute(),
                'verificationUrl' => $this->verificationUrl($notifiable),
            ])
            ->text('mail.verify-email-text', [
                'name' => $notifiable->getDisplayNameAttribute(),
                'verificationUrl' => $this->verificationUrl($notifiable),
            ]);
    }
}
