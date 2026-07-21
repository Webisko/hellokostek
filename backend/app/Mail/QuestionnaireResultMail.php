<?php

namespace App\Mail;

use App\Models\QuestionnaireSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuestionnaireResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly QuestionnaireSubmission $submission,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Twoj wynik ankiety Curandera',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.questionnaires.result-customer',
        );
    }
}