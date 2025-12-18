<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecurePassword implements ValidationRule
{
    /**
     * Minimum password length.
     */
    protected int $minLength;

    /**
     * Whether to require uppercase letters.
     */
    protected bool $requireUppercase;

    /**
     * Whether to require lowercase letters.
     */
    protected bool $requireLowercase;

    /**
     * Whether to require numbers.
     */
    protected bool $requireNumbers;

    /**
     * Whether to require special characters.
     */
    protected bool $requireSpecialChars;

    /**
     * Common passwords to block.
     *
     * @var array<string>
     */
    protected array $commonPasswords = [
        'password', 'password123', '123456', '123456789', 'qwerty',
        'abc123', 'monkey', '1234567', 'letmein', 'trustno1',
        'dragon', 'baseball', 'iloveyou', 'master', 'sunshine',
        'ashley', 'bailey', 'passw0rd', 'shadow', '123123',
        '654321', 'superman', 'qazwsx', 'michael', 'football',
        'password1', 'password12', 'password!', 'admin', 'admin123',
        'welcome', 'welcome1', '12345678', '123456789', '1234567890',
        'boxibox', 'boxibox123', 'storage', 'selfstorage',
    ];

    /**
     * Create a new rule instance.
     */
    public function __construct(
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
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

        // Check minimum length
        if (strlen($value) < $this->minLength) {
            $fail(__('validation.min.string', ['min' => $this->minLength]));
            return;
        }

        // Check for uppercase letters
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $fail(__('validation.password.mixed'));
            return;
        }

        // Check for lowercase letters
        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $fail(__('validation.password.mixed'));
            return;
        }

        // Check for numbers
        if ($this->requireNumbers && !preg_match('/[0-9]/', $value)) {
            $fail(__('validation.password.numbers'));
            return;
        }

        // Check for special characters
        if ($this->requireSpecialChars && !preg_match('/[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\'`~;\/]/', $value)) {
            $fail(__('validation.password.symbols'));
            return;
        }

        // Check against common passwords
        if (in_array(strtolower($value), $this->commonPasswords)) {
            $fail(__('validation.password.uncompromised'));
            return;
        }

        // Check for sequential characters
        if ($this->hasSequentialChars($value)) {
            $fail(__('validation.password.uncompromised'));
            return;
        }

        // Check for repeated characters
        if ($this->hasRepeatedChars($value)) {
            $fail(__('validation.password.uncompromised'));
        }
    }

    /**
     * Check if password contains sequential characters.
     */
    protected function hasSequentialChars(string $password): bool
    {
        $sequences = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            'qwertyuiop',
            'asdfghjkl',
            'zxcvbnm',
        ];

        $lower = strtolower($password);

        foreach ($sequences as $sequence) {
            for ($i = 0; $i <= strlen($sequence) - 4; $i++) {
                $sub = substr($sequence, $i, 4);
                if (str_contains($lower, strtolower($sub))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if password contains too many repeated characters.
     */
    protected function hasRepeatedChars(string $password): bool
    {
        return preg_match('/(.)\1{2,}/', $password);
    }
}
