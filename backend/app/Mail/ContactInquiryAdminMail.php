<?php

namespace App\Mail;

use App\Models\ContactInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ContactInquiry $inquiry,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = 'Nowe zapytanie o wycenę portretu: ' . $this->inquiry->name;
        if (filled($this->inquiry->subject)) {
            $subject .= ' (' . $this->inquiry->subject . ')';
        }

        return new Envelope(
            subject: $subject,
            replyTo: [
                new Address($this->inquiry->email, $this->inquiry->name),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiries.new-inquiry-admin',
            with: [
                'inquiry' => $this->inquiry,
                'cmsUrl' => url('/admin/zapytania-kontaktowe?record=' . $this->inquiry->id),
            ],
        );
    }
}
