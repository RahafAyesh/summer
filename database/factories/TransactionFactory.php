<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\IncomeCategory;
use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(TransactionType::cases());
        $category = $type === TransactionType::Expense
            ? fake()->randomElement(ExpenseCategory::cases())
            : fake()->randomElement(IncomeCategory::cases());

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'amount' => fake()->randomFloat(2, 10, 5000),
            'category' => $category->value,
            'source' => fake()->optional(0.3)->company(),
            'description' => fake()->optional(0.7)->sentence(3),
            'notes' => fake()->optional(0.2)->paragraph(),
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Income,
            'category' => fake()->randomElement(IncomeCategory::cases())->value,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Expense,
            'category' => fake()->randomElement(ExpenseCategory::cases())->value,
        ]);
    }
}
