<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNotificationPreference extends Model
{
    protected $fillable = [
        'customer_id',
        'email_invoices',
        'email_reminders',
        'email_marketing',
        'sms_invoices',
        'sms_reminders',
        'push_enabled',
    ];

    protected $casts = [
        'email_invoices' => 'boolean',
        'email_reminders' => 'boolean',
        'email_marketing' => 'boolean',
        'sms_invoices' => 'boolean',
        'sms_reminders' => 'boolean',
        'push_enabled' => 'boolean',
    ];

    /**
     * Get the customer that owns these preferences.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
