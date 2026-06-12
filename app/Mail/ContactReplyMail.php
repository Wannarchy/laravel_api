<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public string $replyBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: [Contact CYNA] '.$this->contactMessage->sujet,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact-reply',
        );
    }
}
