<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ServiceCommunicator
{
    private ServiceRegistry $serviceRegistry;
    private array $circuitBreakers = [];
    private int $maxRetries = 3;
    private int $retryDelay = 1000; // milliseconds

    public function __construct(ServiceRegistry $serviceRegistry)
    {
        $this->serviceRegistry = $serviceRegistry;
    }

    /**
     * Make a request to a service with full resilience patterns
     */
    public function request(
        string $serviceName,
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        array $fallbackData = null
    ) {
        $correlationId = $this->generateCorrelationId();
        $service = $this->serviceRegistry->getService($serviceName);

        if (!$service) {
            throw new Exception("Unknown service: {$serviceName}");
        }

        // Add correlation and tracing headers
        $headers = array_merge($headers, [
            'X-Correlation-ID' => $correlationId,
            'X-Service-Source' => 'backend-service',
            'X-Request-ID' => (string) Str::uuid(),
        ]);

        $circuitBreaker = $this->getCircuitBreaker($serviceName, $service);

        Log::info("Service request initiated", [
            'service' => $serviceName,
            'method' => $method,
            'endpoint' => $endpoint,
            'correlation_id' => $correlationId,
            'circuit_breaker_state' => $circuitBreaker->getState(),
        ]);

        return $circuitBreaker->execute(function () use ($service, $method, $endpoint, $data, $headers, $serviceName, $correlationId) {
            return $this->makeHttpRequest($service, $method, $endpoint, $data, $headers, $serviceName, $correlationId);
        }, $fallbackData);
    }

    /**
     * Make the actual HTTP request with retries
     */
    private function makeHttpRequest(
        array $service,
        string $method,
        string $endpoint,
        array $data,
        array $headers,
        string $serviceName,
        string $correlationId
    ) {
        $url = rtrim($service['url'], '/') . '/' . ltrim($endpoint, '/');
        $retries = $service['retries'] ?? $this->maxRetries;
        $timeout = $service['timeout'] ?? 30;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $startTime = microtime(true);

                Log::debug("HTTP request attempt", [
                    'service' => $serviceName,
                    'attempt' => $attempt,
                    'max_retries' => $retries,
                    'url' => $url,
                    'correlation_id' => $correlationId,
                ]);

                $httpClient = Http::timeout($timeout)->withHeaders($headers);

                $response = match (strtoupper($method)) {
                    'GET' => $httpClient->get($url, $data),
                    'POST' => $httpClient->post($url, $data),
                    'PUT' => $httpClient->put($url, $data),
                    'PATCH' => $httpClient->patch($url, $data),
                    'DELETE' => $httpClient->delete($url, $data),
                    default => throw new Exception("Unsupported HTTP method: {$method}")
                };

                $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

                if ($response->successful()) {
                    Log::info("Service request successful", [
                        'service' => $serviceName,
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'status' => $response->status(),
                        'duration_ms' => round($duration, 2),
                        'attempt' => $attempt,
                        'correlation_id' => $correlationId,
                    ]);

                    return $response;
                } else {
                    $shouldRetry = $this->shouldRetry($response->status(), $attempt, $retries);

                    Log::warning("Service request failed", [
                        'service' => $serviceName,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'duration_ms' => round($duration, 2),
                        'attempt' => $attempt,
                        'will_retry' => $shouldRetry,
                        'correlation_id' => $correlationId,
                    ]);

                    if (!$shouldRetry) {
                        throw new Exception("Service {$serviceName} returned status {$response->status()}: {$response->body()}");
                    }
                }
            } catch (Exception $e) {
                $shouldRetry = $attempt < $retries;

                Log::error("Service request exception", [
                    'service' => $serviceName,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'will_retry' => $shouldRetry,
                    'correlation_id' => $correlationId,
                ]);

                if (!$shouldRetry) {
                    throw $e;
                }
            }

            // Exponential backoff with jitter
            if ($attempt < $retries) {
                $delay = $this->retryDelay * (2 ** ($attempt - 1)) + random_int(0, 1000);
                usleep($delay * 1000); // Convert to microseconds
            }
        }

        throw new Exception("Service {$serviceName} failed after {$retries} attempts");
    }

    /**
     * Determine if request should be retried based on status code
     */
    private function shouldRetry(int $statusCode, int $attempt, int $maxRetries): bool
    {
        if ($attempt >= $maxRetries) {
            return false;
        }

        // Retry on server errors and specific client errors
        return in_array($statusCode, [
            429, // Too Many Requests
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
            504, // Gateway Timeout
        ]);
    }

    /**
     * Get or create circuit breaker for service
     */
    private function getCircuitBreaker(string $serviceName, array $service): CircuitBreaker
    {
        if (!isset($this->circuitBreakers[$serviceName])) {
            $this->circuitBreakers[$serviceName] = new CircuitBreaker(
                $serviceName,
                $service['circuit_breaker_threshold'] ?? 5
            );
        }

        return $this->circuitBreakers[$serviceName];
    }

    /**
     * Generate correlation ID for request tracing
     */
    private function generateCorrelationId(): string
    {
        return 'gw-' . Str::uuid();
    }

    /**
     * Convenience method for GET requests
     */
    public function get(string $serviceName, string $endpoint, array $params = [], array $headers = [], array $fallbackData = null)
    {
        return $this->request($serviceName, 'GET', $endpoint, $params, $headers, $fallbackData);
    }

    /**
     * Convenience method for POST requests
     */
    public function post(string $serviceName, string $endpoint, array $data = [], array $headers = [], array $fallbackData = null)
    {
        return $this->request($serviceName, 'POST', $endpoint, $data, $headers, $fallbackData);
    }

    /**
     * Convenience method for PUT requests
     */
    public function put(string $serviceName, string $endpoint, array $data = [], array $headers = [], array $fallbackData = null)
    {
        return $this->request($serviceName, 'PUT', $endpoint, $data, $headers, $fallbackData);
    }

    /**
     * Convenience method for DELETE requests
     */
    public function delete(string $serviceName, string $endpoint, array $data = [], array $headers = [], array $fallbackData = null)
    {
        return $this->request($serviceName, 'DELETE', $endpoint, $data, $headers, $fallbackData);
    }

    /**
     * Get circuit breaker status for all services
     */
    public function getCircuitBreakerStatuses(): array
    {
        $statuses = [];

        foreach ($this->circuitBreakers as $serviceName => $circuitBreaker) {
            $statuses[$serviceName] = $circuitBreaker->getStatus();
        }

        return $statuses;
    }

    /**
     * Reset circuit breaker for a service
     */
    public function resetCircuitBreaker(string $serviceName): bool
    {
        if (isset($this->circuitBreakers[$serviceName])) {
            $this->circuitBreakers[$serviceName]->reset();
            return true;
        }

        return false;
    }
}
