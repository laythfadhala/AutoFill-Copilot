@extends('layouts.app')

@section('title', 'Billing & Subscriptions')

@php
    use App\Enums\SubscriptionPlan;
@endphp

@section('styles')
    @livewireStyles
    <style>
        .plan-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .plan-card.current-plan {
            border-color: #007bff;
            background-color: #f8f9ff;
        }

        .plan-card.popular {
            border-color: #28a745;
            position: relative;
        }

        .plan-card.popular::before {
            content: 'Most Popular';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .usage-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .usage-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #28a745);
            transition: width 0.3s ease;
        }

        .trial-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Billing & Subscriptions</h1>

                @if (request('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        🎉 Payment successful! Your subscription is being activated.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Current Usage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Current Usage ({{ now()->format('F Y') }})</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $tokensUsed = $user->getTokensUsedThisMonth();
                            $tokensLimit = $user->getTokenLimit();
                            $tokensRemaining = max(0, $tokensLimit - $tokensUsed);
                            $usagePercentage = $tokensLimit > 0 ? min(100, ($tokensUsed / $tokensLimit) * 100) : 0;
                            $availablePercentage = max(0, 100 - $usagePercentage);
                            $isOverLimit = $tokensUsed > $tokensLimit;
                        @endphp

                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">AI Tokens</span>
                                        <div class="d-flex align-items-center gap-1">
                                            @if ($user->subscription_plan === SubscriptionPlan::FREE->value)
                                                <x-info-tooltip
                                                    message="Your token limit adjusts based on total app usage to ensure fair access for all free users. Upgrade for guaranteed high limits." />
                                            @endif
                                            <span class="badge {{ $isOverLimit ? 'bg-danger' : 'bg-primary' }}">
                                                {{ number_format($tokensUsed) }} / {{ number_format($tokensLimit) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar {{ $isOverLimit ? 'bg-danger' : 'bg-primary' }}"
                                            role="progressbar" style="width: {{ $usagePercentage }}%"
                                            aria-valuenow="{{ $tokensUsed }}" aria-valuemin="0"
                                            aria-valuemax="{{ $tokensLimit }}">
                                        </div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">
                                        {{ number_format($tokensRemaining) }} tokens remaining
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="display-6 {{ $isOverLimit ? 'text-danger' : 'text-primary' }} fw-bold">
                                    {{ number_format($availablePercentage, 0) }}%
                                </div>
                                <small class="text-muted">Available</small>
                            </div>
                        </div>

                        @if ($user->subscription_plan !== SubscriptionPlan::FREE->value)
                            <div class="mt-3 text-center border-top pt-3">
                                @if ($user->stripe_customer_id)
                                    <form action="{{ route('stripe.portal') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-cog me-1"></i> Manage Subscription
                                        </button>
                                    </form>
                                @else
                                    <div class="alert alert-warning">
                                        No Stripe customer ID found. Please contact support.
                                    </div>
                                @endif
                                <small class="d-block mt-2 text-muted">Update payment method, view invoices, or cancel
                                    subscription</small>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Subscription Plans -->
                <h3 class="mb-4">Choose Your Plan</h3>
                <div class="row">
                    @foreach ($plans as $planKey => $plan)
                        <div class="col-md-4 mb-4" id="plan-{{ $planKey }}">
                            <div
                                class="plan-card card h-100 {{ $user->subscription_plan === $planKey ? 'current-plan' : '' }} {{ $plan['popular'] ?? false ? 'popular' : '' }}">
                                <div class="card-body d-flex flex-column">
                                    <div class="text-center mb-3">
                                        <h4 class="card-title">{{ $plan['name'] }}</h4>
                                        <div class="h2 text-primary">
                                            @if ($plan['price'] > 0)
                                                ${{ number_format($plan['price'] / 100, 2) }}
                                                <small class="text-muted">/month</small>
                                            @else
                                                Free
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="mb-2"><strong>🧠 {{ $plan['tokens'] }} tokens</strong></div>
                                    </div>

                                    <ul class="list-unstyled mb-4 grow">
                                        @foreach ($plan['features'] as $feature)
                                            <li class="mb-1">
                                                ✓ {{ $feature }}
                                                @if ($planKey === SubscriptionPlan::FREE->value && $feature === 'Limited tokens based on service usage')
                                                    <x-info-tooltip
                                                        message="Your token limit adjusts based on total app usage to ensure fair access for all free users. Upgrade for guaranteed high limits." />
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="mt-auto">
                                        @if ($user->subscription_plan === $planKey)
                                            <button class="btn btn-outline-primary w-100" disabled>
                                                Current Plan
                                            </button>
                                        @elseif($planKey === SubscriptionPlan::FREE->value)
                                            <button class="btn btn-outline-secondary w-100" disabled>
                                                Free Plan
                                            </button>
                                        @elseif($planKey === SubscriptionPlan::PLUS->value)
                                            <form action="{{ route('stripe.checkout') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="plan"
                                                    value="{{ SubscriptionPlan::PLUS->value }}">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    Upgrade to Plus
                                                </button>
                                            </form>
                                        @else
                                            <form id="upgrade-form-{{ $planKey }}"
                                                action="{{ route('stripe.checkout') }}" method="POST">
                                                @csrf <input type="hidden" name="plan" value="{{ $planKey }}">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    @if ($user->subscription_status === 'canceled')
                                                        Reactivate Plan
                                                    @else
                                                        Upgrade to {{ $plan['name'] }}
                                                    @endif
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @livewireScripts
@endsection
