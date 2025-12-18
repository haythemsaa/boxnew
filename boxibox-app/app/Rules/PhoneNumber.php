<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Allowed country codes.
     *
     * @var array<string>|null
     */
    protected ?array $allowedCountryCodes;

    /**
     * Whether to allow mobile numbers only.
     */
    protected bool $mobileOnly;

    /**
     * Create a new rule instance.
     */
    public function __construct(?array $allowedCountryCodes = null, bool $mobileOnly = false)
    {
        $this->allowedCountryCodes = $allowedCountryCodes;
        $this->mobileOnly = $mobileOnly;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.string'));
            return;
        }

        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-\.\(\)]/', '', $value);

        // Validate format (international or national)
        if (!preg_match('/^(\+)?[0-9]{7,15}$/', $cleaned)) {
            $fail(__('validation.phone'));
            return;
        }

        // Check country code if restrictions apply
        if ($this->allowedCountryCodes !== null && str_starts_with($cleaned, '+')) {
            $hasValidCode = false;
            foreach ($this->allowedCountryCodes as $code) {
                if (str_starts_with($cleaned, $code)) {
                    $hasValidCode = true;
                    break;
                }
            }
            if (!$hasValidCode) {
                $fail(__('validation.phone_country'));
                return;
            }
        }

        // Check if mobile number (French mobile starts with 06 or 07, or +336/+337)
        if ($this->mobileOnly) {
            $isMobile = preg_match('/^(\+33[67]|0[67])/', $cleaned);
            if (!$isMobile) {
                $fail(__('validation.phone_mobile'));
            }
        }
    }
}
