<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * HTTP Client Service with built-in timeouts and circuit breaker pattern
 * Use this service for all external HTTP requests to ensure proper timeout handling
 */
class HttpClientService
{
    protected int $connectTimeout = 5; // seconds
    protected int $timeout = 30; // seconds
    protected int $retries = 2;
    protected int $retryDelay = 100; // milliseconds

    /**
     * Create a new HTTP client with default timeouts
     */
    public function client(): PendingRequest
    {
        return Http::connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->retry($this->retries, $this->retryDelay, function ($exception, $request) {
                // Only retry on connection errors, not on 4xx/5xx responses
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            });
    }

    /**
     * Create a client with custom timeouts
     */
    public function withTimeout(int $timeout, int $connectTimeout = 5): PendingRequest
    {
        return Http::connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->retry($this->retries, $this->retryDelay);
    }

    /**
     * Create a client for quick requests (e.g., health checks)
     */
    public function quick(): PendingRequest
    {
        return Http::connectTimeout(2)
            ->timeout(5)
            ->retry(1, 50);
    }

    /**
     * Create a client for long-running requests (e.g., file uploads)
     */
    public function long(): PendingRequest
    {
        return Http::connectTimeout(10)
            ->timeout(120)
            ->retry(1, 500);
    }

    /**
     * Make a GET request with automatic timeout and logging
     */
    public function get(string $url, array $query = [], array $headers = []): ?Response
    {
        return $this->request('GET', $url, ['query' => $query], $headers);
    }

    /**
     * Make a POST request with automatic timeout and logging
     */
    public function post(string $url, array $data = [], array $headers = []): ?Response
    {
        return $this->request('POST', $url, ['json' => $data], $headers);
    }

    /**
     * Make a PUT request with automatic timeout and logging
     */
    public function put(string $url, array $data = [], array $headers = []): ?Response
    {
        return $this->request('PUT', $url, ['json' => $data], $headers);
    }

    /**
     * Make a DELETE request with automatic timeout and logging
     */
    public function delete(string $url, array $headers = []): ?Response
    {
        return $this->request('DELETE', $url, [], $headers);
    }

    /**
     * Execute HTTP request with error handling
     */
    protected function request(string $method, string $url, array $options = [], array $headers = []): ?Response
    {
        $startTime = microtime(true);

        try {
            $client = $this->client()->withHeaders($headers);

            $response = match ($method) {
                'GET' => $client->get($url, $options['query'] ?? []),
                'POST' => $client->post($url, $options['json'] ?? []),
                'PUT' => $client->put($url, $options['json'] ?? []),
                'DELETE' => $client->delete($url),
                default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::debug('HTTP request completed', [
                'method' => $method,
                'url' => $this->sanitizeUrl($url),
                'status' => $response->status(),
                'duration_ms' => $duration,
            ]);

            return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('HTTP connection failed', [
                'method' => $method,
                'url' => $this->sanitizeUrl($url),
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return null;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('HTTP request failed', [
                'method' => $method,
                'url' => $this->sanitizeUrl($url),
                'status' => $e->response?->status(),
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return $e->response;
        }
    }

    /**
     * Sanitize URL for logging (remove sensitive params)
     */
    protected function sanitizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        $sanitized = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        if (isset($parsed['path'])) {
            $sanitized .= $parsed['path'];
        }

        // Don't log query params as they may contain secrets
        if (isset($parsed['query'])) {
            $sanitized .= '?[params hidden]';
        }

        return $sanitized;
    }

    /**
     * Set default timeouts
     */
    public function setTimeouts(int $timeout, int $connectTimeout): self
    {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        return $this;
    }

    /**
     * Set retry configuration
     */
    public function setRetries(int $retries, int $delayMs): self
    {
        $this->retries = $retries;
        $this->retryDelay = $delayMs;
        return $this;
    }
}
