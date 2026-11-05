<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Mã đăng nhập Movie Buddy');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.otp',
            text: 'mail.otp-text',
            with: ['code' => $this->code],
        );
    }
}
