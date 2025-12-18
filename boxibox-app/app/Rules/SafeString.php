<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeString implements ValidationRule
{
    /**
     * Dangerous patterns to block.
     *
     * @var array<string>
     */
    protected array $dangerousPatterns = [
        '/<script\b[^>]*>/i',           // Script tags
        '/javascript:/i',                 // JavaScript protocol
        '/on\w+\s*=/i',                   // Event handlers (onclick, onerror, etc.)
        '/data:\s*text\/html/i',          // Data URI HTML
        '/base64,/i',                     // Base64 encoded data
        '/expression\s*\(/i',             // CSS expression
        '/url\s*\(\s*["\']?\s*javascript/i', // CSS URL JavaScript
        '/<\s*iframe/i',                  // Iframe tags
        '/<\s*object/i',                  // Object tags
        '/<\s*embed/i',                   // Embed tags
        '/<\s*form/i',                    // Form tags
        '/<\s*meta/i',                    // Meta tags
        '/<\s*link/i',                    // Link tags
        '/\beval\s*\(/i',                 // eval() calls
        '/\bexec\s*\(/i',                 // exec() calls
        '/\bsystem\s*\(/i',               // system() calls
    ];

    /**
     * SQL injection patterns.
     *
     * @var array<string>
     */
    protected array $sqlPatterns = [
        '/\b(union\s+select|insert\s+into|delete\s+from|drop\s+table|truncate\s+table)\b/i',
        '/;\s*(delete|drop|truncate|update|insert)\b/i',
        '/--\s*$/m',
        '/\b(or|and)\s+[\'\"0-9]+=\s*[\'\"0-9]+/i',
    ];

    /**
     * Whether to check for SQL injection patterns.
     */
    protected bool $checkSql;

    /**
     * Whether to allow HTML tags.
     */
    protected bool $allowHtml;

    /**
     * Create a new rule instance.
     */
    public function __construct(bool $checkSql = true, bool $allowHtml = false)
    {
        $this->checkSql = $checkSql;
        $this->allowHtml = $allowHtml;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        // Check for dangerous XSS patterns
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail(__('validation.safe_string'));
                return;
            }
        }

        // Check for SQL injection patterns
        if ($this->checkSql) {
            foreach ($this->sqlPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $fail(__('validation.safe_string_sql'));
                    return;
                }
            }
        }

        // Check for HTML if not allowed
        if (!$this->allowHtml && strip_tags($value) !== $value) {
            $fail(__('validation.safe_string_html'));
        }
    }
}
