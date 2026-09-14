<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')
                ->nullable()
                ->constrained('ai_conversations')
                ->nullOnDelete();
            $table->foreignId('message_id')
                ->nullable()
                ->constrained('ai_messages')
                ->nullOnDelete();
            $table->string('action_type');
            $table->json('action_details');
            $table->string('status')->default('completed');
            $table->timestamps();

            $table->index(['user_id', 'action_type']);
            $table->index('conversation_id');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_logs');
    }
};
