<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build Vouch's password reset email while retaining Laravel's reset URL
     * generation and configured expiry.
     */
    public function toMail($notifiable): MailMessage
    {
        $expiresIn = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset your Vouch password')
            ->view('mail.reset-password', [
                'name' => $notifiable->getDisplayNameAttribute(),
                'resetUrl' => $this->resetUrl($notifiable),
                'expiresIn' => $expiresIn,
            ])
            ->text('mail.reset-password-text', [
                'name' => $notifiable->getDisplayNameAttribute(),
                'resetUrl' => $this->resetUrl($notifiable),
                'expiresIn' => $expiresIn,
            ]);
    }
}
