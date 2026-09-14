<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => fake()->randomElement(ExpenseCategory::cases())->value,
            'amount' => fake()->randomFloat(2, 100, 3000),
            'month' => fake()->dateTimeBetween('-3 months', '+3 months')->format('Y-m'),
        ];
    }
}
