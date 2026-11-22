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
        $cleanName = trim((string) preg_replace('/[\r\n]+/', ' ', (string) $this->inquiry->name));
        $subject = 'Nowe zapytanie o wycenę portretu: ' . $cleanName;
        if (filled($this->inquiry->subject)) {
            $cleanSubject = trim((string) preg_replace('/[\r\n]+/', ' ', (string) $this->inquiry->subject));
            $subject .= ' (' . $cleanSubject . ')';
        }

        return new Envelope(
            subject: $subject,
            replyTo: [
                new Address($this->inquiry->email, $cleanName),
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
