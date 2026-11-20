<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otpCode,
        public readonly string $documentName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your verification code for {$this->documentName}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.otp-verification',
            text: 'mail.otp-verification-text',
        );
    }
}
