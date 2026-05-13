<?php

namespace App\Mail;

use App\Models\FraudReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FraudReportReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FraudReport $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your fraud report',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.fraud_reports.received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
