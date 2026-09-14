<?php

namespace App\Validation;

use App\Enums\ExpenseCategory;
use App\Enums\IncomeCategory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class TransactionRules
{
    /**
     * Get the full validation rules for a single transaction of the given type.
     *
     * This is the single source of truth shared by the HTTP controller and
     * the AI tools. When $strictDate is true the date must be a literal
     * YYYY-MM-DD string (used by the AI tools so relative text like
     * "today" can never reach the database).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function forType(?string $type, bool $strictDate = false): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', self::categoryRule($type)],
            'description' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'date' => $strictDate
                ? ['required', 'date_format:Y-m-d']
                : ['required', 'date'],
        ];
    }

    /**
     * Get the "sometimes" validation rules for a partial transaction update.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function partial(): array
    {
        return [
            'type' => ['sometimes', Rule::in(['income', 'expense'])],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'category' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Get the allowed category values for a transaction type.
     *
     * @return array<int, string>
     */
    public static function allowedCategories(?string $type): array
    {
        $enum = $type === 'income' ? IncomeCategory::class : ExpenseCategory::class;

        return array_column($enum::cases(), 'value');
    }

    /**
     * Build the category validation rule for a transaction type.
     */
    public static function categoryRule(?string $type): In
    {
        return Rule::in(self::allowedCategories($type));
    }
}
