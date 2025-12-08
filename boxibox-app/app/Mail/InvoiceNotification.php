<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class InvoiceNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public string $type;

    public function __construct(Invoice $invoice, string $type = 'sent')
    {
        $this->invoice = $invoice;
        $this->type = $type;
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'sent' => "Facture #{$this->invoice->invoice_number} - BoxiBox",
            'reminder' => "Rappel: Facture #{$this->invoice->invoice_number} - BoxiBox",
            'overdue' => "Facture en retard #{$this->invoice->invoice_number} - BoxiBox",
            default => "Facture #{$this->invoice->invoice_number} - BoxiBox",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoices.notification',
            with: [
                'invoice' => $this->invoice,
                'type' => $this->type,
                'customer' => $this->invoice->customer,
                'contract' => $this->invoice->contract,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
