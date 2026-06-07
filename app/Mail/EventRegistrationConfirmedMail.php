<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRegistrationConfirmedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<Attachment>  $pdfAttachments
     */
    public function __construct(
        private readonly string $mailSubjectLine,
        private readonly string $mailBodyPlain,
        private readonly array $pdfAttachments = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.event-registration-confirmed',
            text: 'emails.event-registration-confirmed-plain',
            with: [
                'body' => $this->mailBodyPlain,
            ],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return $this->pdfAttachments;
    }
}
