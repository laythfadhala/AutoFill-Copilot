<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TokenUsage;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limitType): Response
    {
        $user = Auth::user();

        // Admins bypass all limits
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        switch ($limitType) {
            case 'tokens':
                if (!$user->canUseTokens($request->input('tokens_used', 1))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You\'ve reached your monthly token limit. Upgrade your plan to continue using AI features.',
                        'error_type' => 'token_limit_exceeded',
                        'upgrade_required' => true,
                        'current_usage' => $user->getTokensUsedThisMonth(),
                        'limit' => $user->getTokenLimit(),
                        'subscription_plan' => $user->subscription_plan,
                        'upgrade_url' => route('billing.subscriptions'),
                        'suggestion' => 'Upgrade to Plus (5M tokens) or Pro (25M tokens) for unlimited AI-powered form filling.',
                    ], 402); // Payment Required
                }
                break;
        }

        return $next($request);
    }
}
