<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

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
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'stripe_customer_id',
        'stripe_subscription_id',
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
     * Check if user has active subscription.
     */
    public function hasActiveSubscription()
    {
        return $this->subscription_status === 'active' &&
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
            ->where('subscription_status', 'active')
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
            ->where('action', \App\Enums\TokenAction::FORM_FILL->value)
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
            return $minutesSaved . ' minute' . ($minutesSaved !== 1 ? 's' : '');
        }

        $hours = floor($minutesSaved / 60);
        $remainingMinutes = $minutesSaved % 60;

        if ($remainingMinutes === 0) {
            return $hours . ' hour' . ($hours !== 1 ? 's' : '');
        }

        return $hours . ' hour' . ($hours !== 1 ? 's' : '') . ' and ' . $remainingMinutes . ' minute' . ($remainingMinutes !== 1 ? 's' : '');
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
}
