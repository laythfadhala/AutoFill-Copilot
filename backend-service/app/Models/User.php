<?php

namespace App\Models;

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
    public function getCurrentPlanAttribute()
    {
        return $this->subscription_plan ?? 'free';
    }

    /**
     * Check if user is on trial.
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
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
        return match($this->current_plan) {
            'free' => $this->getDynamicFreeTokenLimit(),
            'plus' => 5000000, // 5M
            'pro' => 25000000, // 25M
            default => 10000,
        };
    }

    /**
     * Calculate dynamic token limit for free users based on active free user count.
     * More free users = lower limit per user to manage costs.
     */
    public function getDynamicFreeTokenLimit(): int
    {
        // Count active free users (on trial or active free plan)
        $activeFreeUsers = static::where('subscription_plan', 'free')
            ->where(function($query) {
                $query->where('trial_ends_at', '>', now())
                      ->orWhere('subscription_status', 'active');
            })
            ->count();

        // Base allocation: 100K tokens for early stage (up to 10K users)
        // After that, use logarithmic scaling to reduce per-user limit
        $baseLimit = 100000; // 100K tokens
        $userThreshold = 10000; // Keep generous limit up to 10K users
        $minLimit = 10000; // Minimum 10K tokens even with massive user base

        if ($activeFreeUsers <= $userThreshold) {
            return $baseLimit;
        }

        // Logarithmic decay formula: limit = base / log10(users / threshold)
        // This gradually reduces the limit as user count grows beyond threshold
        $scaleFactor = log10($activeFreeUsers / $userThreshold);
        $calculatedLimit = (int) ($baseLimit / (1 + $scaleFactor));

        // Ensure we never go below minimum limit
        return max($calculatedLimit, $minLimit);
    }

    /**
     * Get profile limit based on current plan.
     */
    public function getProfileLimit()
    {
        return match($this->current_plan) {
            'free' => 1,
            'plus' => 10,
            'pro' => null, // unlimited
            default => 1,
        };
    }

    /**
     * Get document limit based on current plan.
     */
    public function getDocumentLimit()
    {
        return match($this->current_plan) {
            'free' => 10,
            'plus' => 50,
            'pro' => null, // unlimited
            default => 10,
        };
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
     * Check if user can create more profiles.
     */
    public function canCreateProfile()
    {
        $limit = $this->getProfileLimit();
        return $limit === null || $this->userProfiles()->count() < $limit;
    }

    /**
     * Check if user can upload more documents.
     */
    public function canUploadDocument()
    {
        $limit = $this->getDocumentLimit();
        // Assuming documents are stored somewhere - you'll need to implement this
        return $limit === null || $this->getDocumentCount() < $limit;
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
