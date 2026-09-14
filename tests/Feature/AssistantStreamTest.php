<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_stream_assistant_responses()
    {
        $response = $this->postJson(route('assistant.stream'), [
            'message' => 'كم صرفت هالشهر؟',
            'history' => [],
        ]);

        $response->assertUnauthorized();
    }

    public function test_first_message_with_empty_history_is_accepted()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('assistant.stream'), [
            'message' => 'كم صرفت هالشهر؟',
            'history' => [],
            'session_id' => 'sess_test-session-id',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    }

    public function test_message_is_required()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('assistant.stream'), [
            'history' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }

    public function test_invalid_session_id_is_rejected()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('assistant.stream'), [
            'message' => 'كم صرفت هالشهر؟',
            'history' => [],
            'session_id' => 'bad session id!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('session_id');
    }
}
