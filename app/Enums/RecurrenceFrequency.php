<?php

namespace App\Enums;

enum RecurrenceFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'أسبوعي',
            self::Monthly => 'شهري',
            self::Yearly => 'سنوي',
        };
    }

    public function intervalDays(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::Monthly => 30,
            self::Yearly => 365,
        };
    }
}
