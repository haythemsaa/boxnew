<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public int $daysOverdue;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->daysOverdue = $invoice->due_date ? now()->diffInDays($invoice->due_date) : 0;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rappel de paiement - Facture #{$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoices.payment-reminder',
            with: [
                'invoice' => $this->invoice,
                'daysOverdue' => $this->daysOverdue,
                'customer' => $this->invoice->customer,
                'amountDue' => $this->invoice->total - ($this->invoice->amount_paid ?? 0),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
