<?php

namespace App\Enums;

enum IncomeCategory: string
{
    case Salary = 'salary';
    case Freelance = 'freelance';
    case Investment = 'investment';
    case Gift = 'gift';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Salary => 'راتب',
            self::Freelance => 'عمل حر',
            self::Investment => 'استثمار',
            self::Gift => 'هدية',
            self::Other => 'أخرى',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Salary => '#85e4c5',
            self::Freelance => '#7fb8ef',
            self::Investment => '#c094e4',
            self::Gift => '#f7bbe6',
            self::Other => '#666666',
        };
    }

    public static function toArray(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label(), 'color' => $c->color()],
            self::cases()
        );
    }
}
