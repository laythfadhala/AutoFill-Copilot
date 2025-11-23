<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\TokenAction;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'microsoft_id',
        'avatar',
        'subscription_plan',
        'pending_plan',
        'payment_status',
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_schedule_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the user profiles for the user.
     */
    public function userProfiles()
    {
        return $this->hasMany(UserProfile::class);
    }

    /**
     * Get the token usages for the user.
     */
    public function tokenUsages()
    {
        return $this->hasMany(TokenUsage::class);
    }

    /**
     * Get the current subscription plan.
     */
    public function getCurrentPlan()
    {
        return $this->subscription_plan ?? SubscriptionPlan::FREE->value;
    }

    /**
     * Check if user has a pending plan change.
     */
    public function hasPendingPlanChange()
    {
        return $this->pending_plan !== null &&
               $this->pending_plan !== $this->subscription_plan &&
               $this->subscription_ends_at !== null;
    }

    /**
     * Get the effective plan (current or pending if downgrading).
     */
    public function getEffectivePlan()
    {
        // If there's a pending change scheduled for the future
        if ($this->hasPendingPlanChange() && $this->subscription_ends_at->isFuture()) {
            return $this->subscription_plan; // Still using current plan
        }

        // If subscription period has ended and there's a pending plan
        if ($this->pending_plan && $this->subscription_ends_at && $this->subscription_ends_at->isPast()) {
            return $this->pending_plan;
        }

        return $this->getCurrentPlan();
    }

    /**
     * Check if user has active subscription.
     */
    public function hasActiveSubscription()
    {
        return $this->subscription_status === SubscriptionStatus::ACTIVE->value &&
               ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    /**
     * Get token limit based on current plan.
     */
    public function getTokenLimit()
    {
        $limits = [
            SubscriptionPlan::FREE->value => $this->getDynamicFreeTokenLimit(),
            SubscriptionPlan::PLUS->value => 5000000, // 5M
            SubscriptionPlan::PRO->value => 25000000, // 25M
        ];

        $plan = $this->getCurrentPlan();

        return $limits[$plan] ?? $limits[SubscriptionPlan::FREE->value];
    }

    /**
     * Calculate dynamic token limit for free users based on active free user count.
     * Fixed 100K tokens per user up to 10,000 users.
     * Beyond 10,000 users, divide 1B token pool among all free users.
     */
    public function getDynamicFreeTokenLimit(): int
    {
        // Count active free users
        $activeFreeUsers = static::where('subscription_plan', SubscriptionPlan::FREE->value)
            ->where('subscription_status', SubscriptionStatus::ACTIVE->value)
            ->count();

        $userThreshold = 10000; // Keep fixed 100K limit up to 10K users
        $baseLimit = 100000; // 100K tokens per user
        $totalPoolLimit = 1000000000; // 1 billion token pool

        // Up to 10K users: everyone gets 100K tokens
        if ($activeFreeUsers <= $userThreshold) {
            return $baseLimit;
        }

        // Beyond 10K users: divide 1B pool among all users
        return (int) floor($totalPoolLimit / $activeFreeUsers);
    }

    /**
     * Get tokens used this month.
     */
    public function getTokensUsedThisMonth()
    {
        return $this->tokenUsages()
            ->sum('tokens_this_month');
    }

    /**
     * Get forms filled this month.
     */
    public function getFormsFilledThisMonth()
    {
        $formRecord = $this->tokenUsages()
            ->where('action', TokenAction::FORM_FILL->value)
            ->first();

        return $formRecord?->count_this_month ?? 0;
    }

    /**
     * Get time saved this month in human readable format.
     */
    public function getTimeSavedThisMonth()
    {
        $formsFilled = $this->getFormsFilledThisMonth();
        $minutesSaved = $formsFilled * 5; // 5 minutes per form

        if ($minutesSaved < 60) {
            return $minutesSaved.' minute'.($minutesSaved !== 1 ? 's' : '');
        }

        $hours = floor($minutesSaved / 60);
        $remainingMinutes = $minutesSaved % 60;

        if ($remainingMinutes === 0) {
            return $hours.' hour'.($hours !== 1 ? 's' : '');
        }

        return $hours.' hour'.($hours !== 1 ? 's' : '').' and '.$remainingMinutes.' minute'.($remainingMinutes !== 1 ? 's' : '');
    }

    /**
     * Check if user can use tokens.
     */
    public function canUseTokens($amount = 1)
    {
        return ($this->getTokensUsedThisMonth() + $amount) <= $this->getTokenLimit();
    }

    /**
     * Get document count (counts documents stored in user profiles).
     */
    public function getDocumentCount()
    {
        $totalDocuments = 0;

        foreach ($this->userProfiles as $profile) {
            $data = $profile->data ?? [];

            // Count documents in each profile's data
            // Documents are stored as filename => document_record pairs
            if (is_array($data)) {
                foreach ($data as $filename => $documentRecord) {
                    // Check if this is a document record (has the expected structure)
                    if (is_array($documentRecord) &&
                        isset($documentRecord['filename']) &&
                        isset($documentRecord['uploaded_at']) &&
                        isset($documentRecord['file_path'])) {
                        $totalDocuments++;
                    }
                }
            }
        }

        return $totalDocuments;
    }

    /**
     * Get profile count for this user.
     */
    public function getProfileCount()
    {
        return $this->userProfiles()->count();
    }

    /**
     * Get max profiles allowed for current subscription plan.
     */
    public function getMaxProfiles(): ?int
    {
        $plan = SubscriptionPlan::from($this->getCurrentPlan());

        return $plan->maxProfiles();
    }

    /**
     * Get max documents allowed for current subscription plan.
     */
    public function getMaxDocuments(): ?int
    {
        $plan = SubscriptionPlan::from($this->getCurrentPlan());

        return $plan->maxDocuments();
    }

    /**
     * Check if user can create a new profile.
     */
    public function canCreateProfile(): bool
    {
        $maxProfiles = $this->getMaxProfiles();

        // null means unlimited (for paid plans)
        if ($maxProfiles === null) {
            return true;
        }

        return $this->getProfileCount() < $maxProfiles;
    }

    /**
     * Get pending plan change information for display.
     */
    public function getPendingPlanInfo(): ?array
    {
        if (! $this->hasPendingPlanChange()) {
            return null;
        }

        $currentPlanEnum = SubscriptionPlan::from($this->subscription_plan);
        $pendingPlanEnum = SubscriptionPlan::from($this->pending_plan);

        return [
            'current_plan' => $this->subscription_plan,
            'current_plan_label' => $currentPlanEnum->label(),
            'pending_plan' => $this->pending_plan,
            'pending_plan_label' => $pendingPlanEnum->label(),
            'effective_date' => $this->subscription_ends_at,
            'is_downgrade' => $this->isDowngrade(),
            'is_upgrade' => $this->isUpgrade(),
            'days_remaining' => $this->subscription_ends_at ?
                Carbon::now()->diffInDays($this->subscription_ends_at, false) : null,
        ];
    }

    /**
     * Check if pending change is a downgrade.
     */
    public function isDowngrade(): bool
    {
        if (! $this->pending_plan) {
            return false;
        }

        $planHierarchy = [
            SubscriptionPlan::FREE->value => 0,
            SubscriptionPlan::PLUS->value => 1,
            SubscriptionPlan::PRO->value => 2,
        ];

        return ($planHierarchy[$this->pending_plan] ?? 0) < ($planHierarchy[$this->subscription_plan] ?? 0);
    }

    /**
     * Check if pending change is an upgrade.
     */
    public function isUpgrade(): bool
    {
        if (! $this->pending_plan) {
            return false;
        }

        $planHierarchy = [
            SubscriptionPlan::FREE->value => 0,
            SubscriptionPlan::PLUS->value => 1,
            SubscriptionPlan::PRO->value => 2,
        ];

        return ($planHierarchy[$this->pending_plan] ?? 0) > ($planHierarchy[$this->subscription_plan] ?? 0);
    }

    /**
     * Check if user can upload more documents.
     */
    public function canUploadDocument(int $additionalDocuments = 1): bool
    {
        $maxDocuments = $this->getMaxDocuments();

        // null means unlimited (for paid plans)
        if ($maxDocuments === null) {
            return true;
        }

        return ($this->getDocumentCount() + $additionalDocuments) <= $maxDocuments;
    }

    /**
     * Check if profile limit is reached.
     */
    public function isProfileLimitReached(): bool
    {
        return ! $this->canCreateProfile();
    }

    /**
     * Check if document limit is reached.
     */
    public function isDocumentLimitReached(): bool
    {
        return ! $this->canUploadDocument();
    }
}
