@php
    use App\Enums\SubscriptionPlan;
@endphp

@auth
    @if (auth()->user()->subscription_plan === SubscriptionPlan::FREE->value &&
            auth()->user()->getTokensUsedThisMonth() >= auth()->user()->getTokenLimit())
        <div class="container mt-3">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="fas fa-rocket"></i> You've reached your monthly limit!</h5>
                <p class="mb-2">Great news! You've been getting amazing value from AutoFill Copilot this month. You've used
                    all {{ number_format(auth()->user()->getTokenLimit()) }} tokens available on the free plan.</p>
                <p class="mb-2"><strong>Your tokens will reset on
                        {{ now()->startOfMonth()->addMonth()->format('F 1, Y') }}</strong></p>
                <p class="mb-3">Ready to keep the momentum going? Upgrade now to unlock:</p>
                <a href="{{ route('billing.subscriptions') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-up"></i> Upgrade Now
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @elseif(auth()->user()->subscription_plan === SubscriptionPlan::FREE->value &&
            auth()->user()->getTokensUsedThisMonth() / auth()->user()->getTokenLimit() > 0.8)
        <div class="container mt-3">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> You're almost at your monthly limit
                </h5>
                <p class="mb-2">You've used {{ number_format(auth()->user()->getTokensUsedThisMonth()) }} of
                    {{ number_format(auth()->user()->getTokenLimit()) }} tokens this month
                    ({{ round((auth()->user()->getTokensUsedThisMonth() / auth()->user()->getTokenLimit()) * 100) }}%)
                    .</p>
                <p class="mb-2"><strong>Your tokens will reset on
                        {{ now()->startOfMonth()->addMonth()->format('F 1, Y') }}</strong></p>
                <p class="mb-0">Consider <a href="{{ route('billing.subscriptions') }}" class="alert-link">upgrading to
                        Plus or Pro</a> to avoid interruptions.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
@endauth
