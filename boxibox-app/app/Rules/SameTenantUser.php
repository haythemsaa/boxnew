<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a user ID belongs to the same tenant as the authenticated user.
 * SECURITY: Prevents cross-tenant data access.
 */
class SameTenantUser implements ValidationRule
{
    protected ?int $tenantId;

    public function __construct(?int $tenantId = null)
    {
        $this->tenantId = $tenantId ?? auth()->user()?->tenant_id;
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

        $user = User::find($value);

        if (!$user) {
            $fail('The selected user does not exist.');
            return;
        }

        if ($user->tenant_id !== $this->tenantId) {
            $fail('The selected user does not belong to your organization.');
        }
    }
}
