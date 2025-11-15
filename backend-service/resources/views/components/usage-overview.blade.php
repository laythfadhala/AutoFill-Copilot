@props(['subscriptionData'])

<!-- Unified Usage & Limits Overview -->
@php
    // Calculate all limit statuses
    $tokenUsage = $subscriptionData['usage'];
    $limits = $subscriptionData['limits'];
    $counts = $subscriptionData['counts'];

    $isNearTokenLimit = $tokenUsage['is_near_limit'];
    $isOverTokenLimit = $tokenUsage['is_over_limit'];

    $profileLimit = $limits['profiles'];
    $profileCount = $counts['profiles'];
    $isNearProfileLimit = $profileLimit && $profileCount >= $profileLimit * 0.8;
    $isOverProfileLimit = $profileLimit && $profileCount >= $profileLimit;

    $documentLimit = $limits['documents'];
    $documentCount = $counts['documents'];
    $isNearDocumentLimit = $documentLimit && $documentCount >= $documentLimit * 0.8;
    $isOverDocumentLimit = $documentLimit && $documentCount >= $documentLimit;

    $hasAnyWarnings =
        $isOverTokenLimit ||
        $isNearTokenLimit ||
        $isOverProfileLimit ||
        $isNearProfileLimit ||
        $isOverDocumentLimit ||
        $isNearDocumentLimit;
    $hasCriticalIssues = $isOverTokenLimit || $isOverProfileLimit || $isOverDocumentLimit;
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
                    <div class="col-md-4">
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

                    <!-- Profile Usage -->
                    @if ($profileLimit)
                        <div class="col-md-4">
                            <div
                                class="border rounded p-3 {{ $isOverProfileLimit ? 'border-danger' : ($isNearProfileLimit ? 'border-warning' : 'border-secondary') }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">Profiles</span>
                                    <span
                                        class="badge {{ $isOverProfileLimit ? 'bg-danger' : ($isNearProfileLimit ? 'bg-warning' : 'bg-secondary') }}">
                                        {{ number_format($profileCount) }}/{{ number_format($profileLimit) }}
                                    </span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar {{ $isOverProfileLimit ? 'bg-danger' : ($isNearProfileLimit ? 'bg-warning' : 'bg-success') }}"
                                        style="width: {{ min(100, ($profileCount / $profileLimit) * 100) }}%">
                                    </div>
                                </div>
                                @if ($isOverProfileLimit)
                                    <small class="text-danger fw-semibold">Profile creation disabled</small>
                                @elseif ($isNearProfileLimit)
                                    <small class="text-warning fw-semibold">Near limit</small>
                                @else
                                    <small class="text-muted">Available</small>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Document Usage -->
                    @if ($documentLimit)
                        <div class="col-md-4">
                            <div
                                class="border rounded p-3 {{ $isOverDocumentLimit ? 'border-danger' : ($isNearDocumentLimit ? 'border-warning' : 'border-secondary') }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">Documents</span>
                                    <span
                                        class="badge {{ $isOverDocumentLimit ? 'bg-danger' : ($isNearDocumentLimit ? 'bg-warning' : 'bg-secondary') }}">
                                        {{ number_format($documentCount) }}/{{ number_format($documentLimit) }}
                                    </span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar {{ $isOverDocumentLimit ? 'bg-danger' : ($isNearDocumentLimit ? 'bg-warning' : 'bg-success') }}"
                                        style="width: {{ min(100, ($documentCount / $documentLimit) * 100) }}%">
                                    </div>
                                </div>
                                @if ($isOverDocumentLimit)
                                    <small class="text-danger fw-semibold">Upload disabled</small>
                                @elseif ($isNearDocumentLimit)
                                    <small class="text-warning fw-semibold">Near limit</small>
                                @else
                                    <small class="text-muted">Available</small>
                                @endif
                            </div>
                        </div>
                    @endif
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
