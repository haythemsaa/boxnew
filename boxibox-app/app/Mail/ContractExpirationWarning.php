<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractExpirationWarning extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Contract $contract;
    public int $daysUntilExpiry;

    public function __construct(Contract $contract, int $daysUntilExpiry = 30)
    {
        $this->contract = $contract;
        $this->daysUntilExpiry = $daysUntilExpiry;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Votre contrat expire dans {$this->daysUntilExpiry} jours - BoxiBox",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contracts.expiration-warning',
            with: [
                'contract' => $this->contract,
                'daysUntilExpiry' => $this->daysUntilExpiry,
                'customer' => $this->contract->customer,
                'box' => $this->contract->box,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
