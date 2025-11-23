<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case FREE = 'free';
    case PLUS = 'plus';
    case PRO = 'pro';

    /**
     * Get all plan values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get plan display name
     */
    public function label(): string
    {
        return match($this) {
            self::FREE => 'Basic',
            self::PLUS => 'Plus',
            self::PRO => 'Professional',
        };
    }

    /**
     * Get plan token limit
     */
    public function tokenLimit(): int
    {
        return match($this) {
            self::FREE => 0, // Dynamic calculation
            self::PLUS => 5000000,
            self::PRO => 25000000,
        };
    }

    /**
     * Get maximum number of profiles allowed
     */
    public function maxProfiles(): ?int
    {
        return match($this) {
            self::FREE => 1,
            self::PLUS => null, // Unlimited
            self::PRO => null, // Unlimited
        };
    }

    /**
     * Get maximum number of documents allowed
     */
    public function maxDocuments(): ?int
    {
        return match($this) {
            self::FREE => 5,
            self::PLUS => null, // Unlimited
            self::PRO => null, // Unlimited
        };
    }

    /**
     * Check if plan is free
     */
    public function isFree(): bool
    {
        return $this === self::FREE;
    }

    /**
     * Check if plan is paid
     */
    public function isPaid(): bool
    {
        return !$this->isFree();
    }

    /**
     * Get plan price in cents (Stripe format)
     */
    public function priceInCents(): int
    {
        return match($this) {
            self::FREE => 0,
            self::PLUS => 999, // $9.99
            self::PRO => 4999, // $49.99
        };
    }

    /**
     * Get plan price in dollars
     */
    public function priceInDollars(): float
    {
        return $this->priceInCents() / 100;
    }
}
