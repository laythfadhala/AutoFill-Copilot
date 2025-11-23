<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if payment is paid
     */
    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Check if payment is unpaid
     */
    public function isUnpaid(): bool
    {
        return $this === self::UNPAID;
    }
}
