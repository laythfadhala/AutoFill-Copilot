<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
    case PAST_DUE = 'past_due';
    case INCOMPLETE = 'incomplete';

    /**
     * Get all status values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status display label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::CANCELED => 'Canceled',
            self::PAST_DUE => 'Past Due',
            self::INCOMPLETE => 'Incomplete',
        };
    }

    /**
     * Check if subscription is in good standing
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if subscription needs attention
     */
    public function needsAttention(): bool
    {
        return in_array($this, [self::PAST_DUE, self::INCOMPLETE]);
    }
}
