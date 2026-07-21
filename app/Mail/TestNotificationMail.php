<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $shopName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kiểm tra cấu hình Email SMTP thành công');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notification-test');
    }
}
