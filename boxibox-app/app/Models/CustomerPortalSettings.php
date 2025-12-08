<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPortalSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'welcome_message',
    ];

    protected $casts = [
        'enable_online_payment' => 'boolean',
        'enable_auto_pay' => 'boolean',
        'enable_invoice_download' => 'boolean',
        'enable_contract_view' => 'boolean',
        'enable_maintenance_requests' => 'boolean',
        'enable_box_change_request' => 'boolean',
        'enable_termination_request' => 'boolean',
        'enable_document_upload' => 'boolean',
        'enable_notifications_settings' => 'boolean',
        'enable_payment_methods_management' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function getForTenant(int $tenantId): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'is_enabled' => true,
                'allow_online_payments' => true,
                'allow_auto_pay_setup' => true,
                'allow_contract_viewing' => true,
                'allow_invoice_download' => true,
                'allow_box_change_requests' => true,
                'allow_termination_requests' => true,
                'allow_maintenance_requests' => true,
                'allow_document_upload' => true,
            ]
        );
    }
}
