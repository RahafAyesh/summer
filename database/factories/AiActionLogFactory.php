<?php

namespace Database\Factories;

use App\Enums\AiActionStatus;
use App\Enums\AiActionType;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiActionLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversation_id' => AiConversation::factory(),
            'message_id' => AiMessage::factory(),
            'action_type' => fake()->randomElement(AiActionType::cases()),
            'action_details' => ['amount' => fake()->randomFloat(2, 10, 500), 'category' => 'food'],
            'status' => fake()->randomElement(AiActionStatus::cases()),
        ];
    }
}
