<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should never be sanitized.
     *
     * @var array<string>
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
    ];

    /**
     * Fields that may contain HTML.
     *
     * @var array<string>
     */
    protected array $allowHtml = [
        'content',
        'body',
        'description',
        'notes',
        'terms',
        'email_body',
        'sms_message',
        'template_content',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        $sanitized = $this->sanitizeArray($input);

        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Recursively sanitize array values.
     */
    protected function sanitizeArray(array $array, string $prefix = ''): array
    {
        $sanitized = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (in_array($key, $this->except)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $fullKey);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value, $key);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a string value.
     */
    protected function sanitizeString(string $value, string $key): string
    {
        // Trim whitespace
        $value = trim($value);

        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // For fields that allow HTML, only strip dangerous tags
        if (in_array($key, $this->allowHtml)) {
            return $this->stripDangerousTags($value);
        }

        // For regular fields, encode HTML entities
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }

    /**
     * Strip dangerous HTML tags while keeping safe ones.
     */
    protected function stripDangerousTags(string $value): string
    {
        // Remove script tags and their content
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);

        // Remove event handlers
        $value = preg_replace('/\s*on\w+\s*=\s*(["\']).*?\1/i', '', $value);
        $value = preg_replace('/\s*on\w+\s*=\s*[^\s>]+/i', '', $value);

        // Remove javascript: protocol
        $value = preg_replace('/javascript:/i', '', $value);

        // Remove data: protocol for potential XSS
        $value = preg_replace('/data:\s*text\/html/i', '', $value);

        // Remove dangerous tags
        $dangerousTags = ['script', 'iframe', 'object', 'embed', 'form', 'meta', 'link', 'base'];
        foreach ($dangerousTags as $tag) {
            $value = preg_replace("/<\/?{$tag}\b[^>]*>/i", '', $value);
        }

        return $value;
    }
}
