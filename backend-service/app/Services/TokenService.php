<?php

namespace App\Services;

use App\Models\TokenUsage;
use App\Models\User;
use App\Enums\TokenAction;
use Carbon\Carbon;

class TokenService
{
    /**
     * Calculate tokens used for form filling based on field count.
     */
    public static function calculateFormFillTokens(int $fieldCount): int
    {
        // Base tokens for form filling action
        $baseTokens = 10;

        // Additional tokens per field (more complex fields use more tokens)
        $fieldTokens = $fieldCount * 2;

        return $baseTokens + $fieldTokens;
    }

    /**
     * Calculate tokens used for document processing.
     */
    public static function calculateDocumentTokens(int $documentSizeKb): int
    {
        // Base tokens for document processing
        $baseTokens = 50;

        // Additional tokens based on document size
        $sizeTokens = ceil($documentSizeKb / 10); // 1 token per 10KB

        return $baseTokens + $sizeTokens;
    }

    /**
     * Consume tokens for a specific action.
     */
    public static function consumeTokens(User $user, TokenAction|string $action, int $tokensUsed, array $metadata = []): TokenUsage
    {
        // Convert enum to string if needed
        $actionValue = $action instanceof TokenAction ? $action->value : $action;
        $currentMonth = now()->format('Y-m');

        try {
            // Try to find existing record
            $existingRecord = TokenUsage::where('user_id', $user->id)
                ->where('action', $actionValue)
                ->first();

            if ($existingRecord) {
                // Check if we need to reset monthly counters
                if ($existingRecord->current_month !== $currentMonth) {
                    $existingRecord->tokens_this_month = 0;
                    $existingRecord->count_this_month = 0;
                    $existingRecord->current_month = $currentMonth;
                }

                // Update counters
                $existingRecord->tokens_used += $tokensUsed;
                $existingRecord->tokens_this_month += $tokensUsed;
                $existingRecord->count_this_month++;
                $existingRecord->save();

                return $existingRecord;
            } else {
                // Create new record for this action
                return TokenUsage::create([
                    'user_id' => $user->id,
                    'action' => $actionValue,
                    'tokens_used' => $tokensUsed,
                    'tokens_this_month' => $tokensUsed,
                    'count_this_month' => 1,
                    'current_month' => $currentMonth,
                ]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation - try to find and update existing record
            if ($e->getCode() == 23505) { // PostgreSQL unique violation
                $existingRecord = TokenUsage::where('user_id', $user->id)
                    ->where('action', $actionValue)
                    ->first();

                if ($existingRecord) {
                    // Check if we need to reset monthly counters
                    if ($existingRecord->current_month !== $currentMonth) {
                        $existingRecord->tokens_this_month = 0;
                        $existingRecord->count_this_month = 0;
                        $existingRecord->current_month = $currentMonth;
                    }

                    // Update counters
                    $existingRecord->tokens_used += $tokensUsed;
                    $existingRecord->tokens_this_month += $tokensUsed;
                    $existingRecord->count_this_month++;
                    $existingRecord->save();

                    return $existingRecord;
                }
            }

            // Re-throw if it's not a unique constraint violation or we can't handle it
            throw $e;
        }
    }

    /**
     * Consume actual tokens used from AI API response.
     */
    public static function consumeActualTokens(User $user, TokenAction|string $action, array $aiUsage, array $additionalMetadata = []): TokenUsage
    {
        $totalTokens = $aiUsage['total_tokens'] ?? 0;
        $promptTokens = $aiUsage['prompt_tokens'] ?? 0;
        $completionTokens = $aiUsage['completion_tokens'] ?? 0;
        $cachedTokens = $aiUsage['cached_tokens'] ?? 0;

        $metadata = array_merge($additionalMetadata, [
            'ai_usage' => [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cached_tokens' => $cachedTokens,
                'billable_tokens' => $totalTokens - $cachedTokens, // Cached tokens are typically not billed
            ]
        ]);

        return self::consumeTokens($user, $action, $totalTokens, $metadata);
    }

    /**
     * Check if user can perform an action with given token cost.
     */
    public static function canConsumeTokens(User $user, int $tokensNeeded): bool
    {
        return $user->canUseTokens($tokensNeeded);
    }

    /**
     * Get usage statistics for the current month.
     */
    public static function getMonthlyUsage(User $user): array
    {
        $tokensUsed = $user->getTokensUsedThisMonth();
        $tokensLimit = $user->getTokenLimit();

        return [
            'tokens_used' => $tokensUsed,
            'tokens_limit' => $tokensLimit,
            'tokens_remaining' => max(0, $tokensLimit - $tokensUsed),
            'usage_percentage' => $tokensLimit > 0 ? round(($tokensUsed / $tokensLimit) * 100, 1) : 0,
            'is_near_limit' => ($tokensUsed / $tokensLimit) > 0.8, // 80% usage warning
            'is_over_limit' => $tokensUsed >= $tokensLimit,
        ];
    }

    /**
     * Get trial status and time remaining.
     */
    public static function getTrialStatus(User $user): array
    {
        if (!$user->onTrial()) {
            return [
                'on_trial' => false,
                'trial_ends_at' => null,
                'days_remaining' => 0,
            ];
        }

        $daysRemaining = now()->diffInDays($user->trial_ends_at, false);

        return [
            'on_trial' => true,
            'trial_ends_at' => $user->trial_ends_at,
            'days_remaining' => max(0, $daysRemaining),
            'is_trial_ending' => $daysRemaining <= 3, // Warning when 3 days left
        ];
    }
}
