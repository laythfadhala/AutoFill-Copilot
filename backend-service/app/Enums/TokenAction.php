<?php

namespace App\Enums;

enum TokenAction: string
{
    case FORM_FILL = 'form_fill';
    case FIELD_FILL = 'field_fill';
    case DOCUMENT_PROCESSING = 'document_processing';

    /**
     * Get all action values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get action display name
     */
    public function label(): string
    {
        return match($this) {
            self::FORM_FILL => 'Form Fill',
            self::FIELD_FILL => 'Field Fill',
            self::DOCUMENT_PROCESSING => 'Document Processing',
        };
    }
}
