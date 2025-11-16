@props(['subscriptionData'])

<!-- Unified Usage & Limits Overview -->
@php
    // Calculate all limit statuses
    $tokenUsage = $subscriptionData['usage'];
    $limits = $subscriptionData['limits'];
    $counts = $subscriptionData['counts'];

    $isNearTokenLimit = $tokenUsage['is_near_limit'];
    $isOverTokenLimit = $tokenUsage['is_over_limit'];

    $hasAnyWarnings = $isOverTokenLimit || $isNearTokenLimit;
    $hasCriticalIssues = $isOverTokenLimit;
@endphp

@if ($hasAnyWarnings)
    <div class="alert {{ $hasCriticalIssues ? 'alert-danger' : 'alert-warning' }} mb-4">
        <div class="d-flex align-items-start">
            <i
                class="fas {{ $hasCriticalIssues ? 'fa-exclamation-circle' : 'fa-exclamation-triangle' }} fa-2x me-3 mt-1"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-3">{{ $hasCriticalIssues ? 'Limits Reached' : 'Approaching Limits' }}</h5>

                <div class="row g-3">
                    <!-- Token Usage -->
                    <div class="col-md-12">
                        <div
                            class="border rounded p-3 {{ $isOverTokenLimit ? 'border-danger' : ($isNearTokenLimit ? 'border-warning' : 'border-secondary') }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold">AI Tokens</span>
                                <span
                                    class="badge {{ $isOverTokenLimit ? 'bg-danger' : ($isNearTokenLimit ? 'bg-warning' : 'bg-secondary') }}">
                                    {{ number_format($tokenUsage['tokens']) }}/{{ number_format($limits['tokens']) }}
                                </span>
                            </div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar {{ $isOverTokenLimit ? 'bg-danger' : ($isNearTokenLimit ? 'bg-warning' : 'bg-success') }}"
                                    style="width: {{ min(100, $tokenUsage['usage_percentage']) }}%">
                                </div>
                            </div>
                            @if ($isOverTokenLimit)
                                <small class="text-danger fw-semibold">AI features disabled</small>
                            @elseif ($isNearTokenLimit)
                                <small
                                    class="text-warning fw-semibold">{{ number_format($tokenUsage['usage_percentage'], 1) }}%
                                    used</small>
                            @else
                                <small class="text-muted">{{ number_format($tokenUsage['usage_percentage'], 1) }}%
                                    used</small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="{{ route('billing.subscriptions') }}"
                        class="btn btn-sm {{ $hasCriticalIssues ? 'btn-danger' : 'btn-warning' }}">
                        <i class="fas fa-arrow-up me-1"></i>Upgrade Plan
                    </a>
                    @if ($subscriptionData['trial_status']['on_trial'])
                        <span class="text-muted ms-3">
                            <i class="fas fa-clock me-1"></i>Trial ends in
                            {{ $subscriptionData['trial_status']['days_remaining'] }} days
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
