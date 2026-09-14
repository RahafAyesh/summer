<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->optional(0.7)->sentence(4),
            'status' => 'active',
            'last_message_at' => now(),
        ];
    }
}
