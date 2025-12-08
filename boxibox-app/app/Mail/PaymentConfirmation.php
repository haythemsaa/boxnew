<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public float $amount;

    public function __construct(Invoice $invoice, float $amount)
    {
        $this->invoice = $invoice;
        $this->amount = $amount;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmation de paiement - Facture #{$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payments.confirmation',
            with: [
                'invoice' => $this->invoice,
                'amount' => $this->amount,
                'customer' => $this->invoice->customer,
                'remainingBalance' => max(0, $this->invoice->total - ($this->invoice->amount_paid ?? 0)),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
