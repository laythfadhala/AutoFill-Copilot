@php
    use App\Enums\SubscriptionPlan;
@endphp

@auth
    @php
        $user = auth()->user();
        $tokensUsed = $user->getTokensUsedThisMonth();
        $tokensLimit = $user->getTokenLimit();
        $tokensRemaining = max(0, $tokensLimit - $tokensUsed);
        $isOverLimit = $tokensUsed > $tokensLimit;

        $nextPlan = match ($user->subscription_plan) {
            SubscriptionPlan::FREE->value => [
                'name' => SubscriptionPlan::PLUS->label(),
                'value' => SubscriptionPlan::PLUS->value,
            ],
            SubscriptionPlan::PLUS->value => [
                'name' => SubscriptionPlan::PRO->label(),
                'value' => SubscriptionPlan::PRO->value,
            ],
            default => null,
        };
    @endphp

    @if ($isOverLimit)
        <div class="container mt-3">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Token limit reached!</strong>
                <p class="mb-0 mt-1">
                    Your tokens will reset on
                    <strong>{{ now()->startOfMonth()->addMonth()->format('F 1, Y') }}</strong>.
                    @if ($nextPlan)
                        If you need more tokens, please consider
                        <a href="#" class="alert-link"
                            onclick="event.preventDefault(); document.getElementById('upgrade-form-{{ $nextPlan['value'] }}').submit();">
                            upgrading to {{ $nextPlan['name'] }}
                        </a>.
                        <form id="upgrade-form-{{ $nextPlan['value'] }}" action="{{ route('stripe.checkout') }}"
                            method="POST" class="d-none">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $nextPlan['value'] }}">
                        </form>
                    @else
                        If you need more tokens, please contact support for enterprise options.
                    @endif
                </p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
@endauth
