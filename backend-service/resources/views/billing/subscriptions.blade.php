@extends('layouts.app')

@section('title', 'Billing & Subscriptions')

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

                <!-- Trial Status -->
                @if ($user->onTrial())
                    <div class="trial-banner">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">🎉 You're on a free trial!</h5>
                                <p class="mb-0">Enjoy full access to all features.
                                    {{ $user->trial_ends_at->diffForHumans() }}.</p>
                            </div>
                            <div class="text-end">
                                <div class="h4 mb-0">{{ $user->trial_ends_at->format('M j, Y') }}</div>
                                <small>Trial ends</small>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Current Usage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Current Usage ({{ now()->format('F Y') }})</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-primary">{{ number_format($user->getTokensUsedThisMonth()) }}</div>
                                    <div class="text-muted">Tokens Used</div>
                                    <div class="usage-bar mt-2">
                                        <div class="usage-fill"
                                            style="width: {{ $user->getTokenLimit() > 0 ? min(100, ($user->getTokensUsedThisMonth() / $user->getTokenLimit()) * 100) : 0 }}%">
                                        </div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">
                                        {{ number_format($user->getTokenLimit() - $user->getTokensUsedThisMonth()) }}
                                        remaining
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-success">{{ $user->userProfiles()->count() }}</div>
                                    <div class="text-muted">Profiles</div>
                                    <small class="text-muted mt-1 d-block">
                                        @if ($user->getProfileLimit())
                                            {{ $user->getProfileLimit() - $user->userProfiles()->count() }} remaining
                                        @else
                                            Unlimited
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-info">{{ $user->getDocumentCount() }}</div>
                                    <div class="text-muted">Documents</div>
                                    <small class="text-muted mt-1 d-block">
                                        @if ($user->getDocumentLimit())
                                            {{ $user->getDocumentLimit() - $user->getDocumentCount() }} remaining
                                        @else
                                            Unlimited
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        @if ($user->current_plan !== 'free')
                            <div class="mt-3 text-center">
                                <a href="https://billing.stripe.com/p/login/eVqcN69Oj8V41Ayf4H1ck00"
                                    class="btn btn-outline-primary" target="_blank">
                                    Manage Subscription
                                </a>
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
                        <div class="col-md-4 mb-4">
                            <div
                                class="plan-card card h-100 {{ $user->current_plan === $planKey ? 'current-plan' : '' }} {{ $plan['popular'] ?? false ? 'popular' : '' }}">
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
                                        <div class="mb-2"><strong>👤 {{ $plan['profiles'] }} profiles</strong></div>
                                        <div class="mb-2"><strong>📄 {{ $plan['documents'] }} documents</strong></div>
                                    </div>

                                    <ul class="list-unstyled mb-4 grow">
                                        @foreach ($plan['features'] as $feature)
                                            <li class="mb-1">✓ {{ $feature }}</li>
                                        @endforeach
                                    </ul>

                                    <div class="mt-auto">
                                        @if ($user->current_plan === $planKey)
                                            <button class="btn btn-outline-primary w-100" disabled>
                                                Current Plan
                                            </button>
                                        @elseif($planKey === 'free')
                                            <button class="btn btn-outline-secondary w-100" disabled>
                                                Free Plan
                                            </button>
                                        @elseif($planKey === 'plus')
                                            <form action="{{ route('stripe.checkout') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="plan" value="plus">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    Upgrade to Plus
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('stripe.checkout') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="plan" value="{{ $planKey }}">
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

                <!-- Trial Expiry Warning -->
                @if ($user->onTrial() && $user->trial_ends_at->diffInDays(now()) <= 3)
                    <div class="alert alert-warning">
                        <h5>⚠️ Trial Ending Soon!</h5>
                        <p>Your free trial ends {{ $user->trial_ends_at->diffForHumans() }}. Upgrade now to keep
                            your autofill magic running!</p>
                        <button class="btn btn-warning subscribe-btn" data-plan="pro">
                            Upgrade to Pro - $9.99/month
                        </button>
                    </div>
                @endif

                <!-- Token Limit Reached -->
                @if ($user->getTokensUsedThisMonth() >= $user->getTokenLimit())
                    <div class="alert alert-danger">
                        <h5>🎉 You've used all your trial tokens!</h5>
                        <p>You filled {{ $user->getFormsFilledThisMonth() }} forms and saved approximately
                            {{ $user->getTimeSavedThisMonth() }}. Upgrade to keep your autofill magic
                            running.</p>
                        <button class="btn btn-danger subscribe-btn" data-plan="pro">
                            Upgrade Now - $9.99/month
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @livewireScripts
@endsection
