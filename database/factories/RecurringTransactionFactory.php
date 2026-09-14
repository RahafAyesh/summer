<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(TransactionType::cases()),
            'amount' => fake()->randomFloat(2, 50, 2000),
            'category' => fake()->randomElement(ExpenseCategory::cases())->value,
            'description' => fake()->optional(0.6)->sentence(3),
            'frequency' => fake()->randomElement(RecurrenceFrequency::cases()),
            'next_due_date' => fake()->dateTimeBetween('now', '+2 months'),
            'end_date' => fake()->optional(0.3)->dateTimeBetween('+3 months', '+1 year'),
            'is_active' => true,
        ];
    }
}
