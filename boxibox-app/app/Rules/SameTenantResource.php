<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Validates that a resource ID belongs to the same tenant as the authenticated user.
 * SECURITY: Prevents cross-tenant data access.
 *
 * Usage:
 *   'customer_id' => ['required', new SameTenantResource(Customer::class)],
 *   'site_id' => ['nullable', new SameTenantResource(Site::class)],
 *   'box_id' => ['required', new SameTenantResource(Box::class)],
 */
class SameTenantResource implements ValidationRule
{
    protected string $modelClass;
    protected ?int $tenantId;
    protected string $tenantColumn;

    public function __construct(string $modelClass, ?int $tenantId = null, string $tenantColumn = 'tenant_id')
    {
        $this->modelClass = $modelClass;
        $this->tenantId = $tenantId ?? auth()->user()?->tenant_id;
        $this->tenantColumn = $tenantColumn;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow null/empty values (use 'required' rule if needed)
        }

        if (!$this->tenantId) {
            $fail('Unable to verify tenant context.');
            return;
        }

        if (!class_exists($this->modelClass)) {
            $fail('Invalid resource type.');
            return;
        }

        /** @var Model $model */
        $model = $this->modelClass::find($value);

        if (!$model) {
            $fail('The selected resource does not exist.');
            return;
        }

        $resourceTenantId = $model->{$this->tenantColumn} ?? null;

        if ($resourceTenantId !== $this->tenantId) {
            $fail('The selected resource does not belong to your organization.');
        }
    }
}
