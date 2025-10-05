<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class CircuitBreaker
{
    private string $serviceName;
    private int $failureThreshold;
    private int $recoveryTimeout;
    private int $requestTimeout;

    public function __construct(string $serviceName, int $failureThreshold = 5, int $recoveryTimeout = 60, int $requestTimeout = 30)
    {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout;
        $this->requestTimeout = $requestTimeout;
    }

    /**
     * Execute a callable with circuit breaker protection
     */
    public function execute(callable $callback, array $fallbackData = null)
    {
        $state = $this->getState();

        switch ($state) {
            case 'open':
                Log::warning("Circuit breaker is OPEN for service: {$this->serviceName}");

                if ($fallbackData !== null) {
                    return $fallbackData;
                }

                throw new Exception("Service {$this->serviceName} is temporarily unavailable (circuit breaker open)");

            case 'half-open':
                try {
                    Log::info("Circuit breaker attempting recovery for service: {$this->serviceName}");
                    $result = $callback();
                    $this->recordSuccess();
                    return $result;
                } catch (Exception $e) {
                    $this->recordFailure();

                    if ($fallbackData !== null) {
                        return $fallbackData;
                    }

                    throw $e;
                }

            case 'closed':
            default:
                try {
                    return $callback();
                } catch (Exception $e) {
                    $this->recordFailure();

                    if ($fallbackData !== null) {
                        return $fallbackData;
                    }

                    throw $e;
                }
        }
    }

    /**
     * Get current circuit breaker state
     */
    public function getState(): string
    {
        $failures = $this->getFailureCount();
        $lastFailure = $this->getLastFailureTime();

        if ($failures >= $this->failureThreshold) {
            if ($lastFailure && (time() - $lastFailure) > $this->recoveryTimeout) {
                return 'half-open';
            }
            return 'open';
        }

        return 'closed';
    }

    /**
     * Record a successful request
     */
    private function recordSuccess(): void
    {
        $this->resetFailureCount();
        Log::info("Circuit breaker SUCCESS recorded for service: {$this->serviceName}");
    }

    /**
     * Record a failed request
     */
    private function recordFailure(): void
    {
        $failureCount = $this->getFailureCount() + 1;
        $cacheKey = $this->getFailureCountKey();

        Cache::put($cacheKey, $failureCount, 300); // 5 minutes
        Cache::put($this->getLastFailureTimeKey(), time(), 300);

        Log::warning("Circuit breaker FAILURE recorded for service: {$this->serviceName}", [
            'failure_count' => $failureCount,
            'threshold' => $this->failureThreshold,
        ]);

        if ($failureCount >= $this->failureThreshold) {
            Log::error("Circuit breaker OPENED for service: {$this->serviceName}");
        }
    }

    /**
     * Get current failure count
     */
    private function getFailureCount(): int
    {
        return Cache::get($this->getFailureCountKey(), 0);
    }

    /**
     * Get last failure timestamp
     */
    private function getLastFailureTime(): ?int
    {
        return Cache::get($this->getLastFailureTimeKey());
    }

    /**
     * Reset failure count
     */
    private function resetFailureCount(): void
    {
        Cache::forget($this->getFailureCountKey());
        Cache::forget($this->getLastFailureTimeKey());
    }

    /**
     * Get failure count cache key
     */
    private function getFailureCountKey(): string
    {
        return "circuit_breaker_failures_{$this->serviceName}";
    }

    /**
     * Get last failure time cache key
     */
    private function getLastFailureTimeKey(): string
    {
        return "circuit_breaker_last_failure_{$this->serviceName}";
    }

    /**
     * Manually reset circuit breaker
     */
    public function reset(): void
    {
        $this->resetFailureCount();
        Log::info("Circuit breaker manually reset for service: {$this->serviceName}");
    }

    /**
     * Get circuit breaker status
     */
    public function getStatus(): array
    {
        return [
            'service' => $this->serviceName,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'failure_threshold' => $this->failureThreshold,
            'last_failure' => $this->getLastFailureTime(),
            'recovery_timeout' => $this->recoveryTimeout,
        ];
    }
}
