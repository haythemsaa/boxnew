<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

/**
 * Send Email Job
 * Handles asynchronous email sending to prevent blocking requests
 */
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds between retries
    public int $timeout = 120; // seconds

    protected string $to;
    protected string $subject;
    protected string $htmlContent;
    protected ?string $textContent;
    protected ?array $attachments;
    protected ?string $replyTo;
    protected ?int $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $to,
        string $subject,
        string $htmlContent,
        ?string $textContent = null,
        ?array $attachments = null,
        ?string $replyTo = null,
        ?int $tenantId = null
    ) {
        $this->to = $to;
        $this->subject = $subject;
        $this->htmlContent = $htmlContent;
        $this->textContent = $textContent;
        $this->attachments = $attachments;
        $this->replyTo = $replyTo;
        $this->tenantId = $tenantId;

        // Use emails queue for dedicated processing
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::send([], [], function (Message $message) {
                $message->to($this->to)
                    ->subject($this->subject)
                    ->html($this->htmlContent);

                if ($this->textContent) {
                    $message->text($this->textContent);
                }

                if ($this->replyTo) {
                    $message->replyTo($this->replyTo);
                }

                if ($this->attachments) {
                    foreach ($this->attachments as $attachment) {
                        if (isset($attachment['path'])) {
                            $message->attach($attachment['path'], [
                                'as' => $attachment['name'] ?? null,
                                'mime' => $attachment['mime'] ?? null,
                            ]);
                        }
                    }
                }
            });

            Log::info('Email sent successfully via queue', [
                'to' => $this->to,
                'subject' => $this->subject,
                'tenant_id' => $this->tenantId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email via queue', [
                'to' => $this->to,
                'subject' => $this->subject,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e; // Rethrow to trigger retry
        }
    }

    /**
     * Handle job failure after all retries exhausted
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Email job failed permanently', [
            'to' => $this->to,
            'subject' => $this->subject,
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get unique ID for job deduplication
     */
    public function uniqueId(): string
    {
        return md5($this->to . $this->subject . $this->htmlContent);
    }
}
