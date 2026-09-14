<?php

namespace Database\Factories;

use App\Enums\AiMessageRole;
use App\Models\AiConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => AiConversation::factory(),
            'role' => fake()->randomElement(AiMessageRole::cases()),
            'content' => fake()->paragraph(),
            'metadata' => fake()->optional(0.3)->randomElement([
                ['model' => 'gpt-4', 'tokens_used' => fake()->numberBetween(50, 500)],
                ['model' => 'claude-3', 'tokens_used' => fake()->numberBetween(100, 800)],
            ]),
        ];
    }
}
