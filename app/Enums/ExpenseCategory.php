<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Food = 'food';
    case Transport = 'transport';
    case Bills = 'bills';
    case Health = 'health';
    case Entertainment = 'entertainment';
    case Shopping = 'shopping';
    case Education = 'education';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Food => 'طعام',
            self::Transport => 'مواصلات',
            self::Bills => 'فواتير',
            self::Health => 'صحة',
            self::Entertainment => 'ترفيه',
            self::Shopping => 'تسوق',
            self::Education => 'تعليم',
            self::Other => 'أخرى',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Food => '#ffb760',
            self::Transport => '#7fb8ef',
            self::Bills => '#f89d6b',
            self::Health => '#85e4c5',
            self::Entertainment => '#f7bbe6',
            self::Shopping => '#c094e4',
            self::Education => '#7fb8ef',
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
