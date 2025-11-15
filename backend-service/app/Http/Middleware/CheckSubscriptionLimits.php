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
                        'current_plan' => $user->current_plan,
                        'upgrade_url' => route('billing.subscriptions'),
                        'suggestion' => 'Upgrade to Plus (5M tokens) or Pro (25M tokens) for unlimited AI-powered form filling.',
                    ], 402); // Payment Required
                }
                break;

            case 'profiles':
                if (!$user->canCreateProfile()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You\'ve reached your profile limit. Upgrade to create more profiles.',
                        'error_type' => 'profile_limit_exceeded',
                        'upgrade_required' => true,
                        'current_usage' => $user->userProfiles()->count(),
                        'limit' => $user->getProfileLimit(),
                        'current_plan' => $user->current_plan,
                        'upgrade_url' => route('billing.subscriptions'),
                    ], 402);
                }
                break;

            case 'documents':
                if (!$user->canUploadDocument()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You\'ve reached your document limit. Upgrade to process more documents.',
                        'error_type' => 'document_limit_exceeded',
                        'upgrade_required' => true,
                        'current_usage' => $user->getDocumentCount(),
                        'limit' => $user->getDocumentLimit(),
                        'current_plan' => $user->current_plan,
                        'upgrade_url' => route('billing.subscriptions'),
                    ], 402);
                }
                break;
        }

        return $next($request);
    }
}
