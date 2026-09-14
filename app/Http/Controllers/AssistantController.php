<?php

namespace App\Http\Controllers;

use App\Ai\Agents\FinanceAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\AiManager;
use Laravel\Ai\Streaming\Events\Error;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\ReasoningEnd;
use Laravel\Ai\Streaming\Events\ReasoningStart;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Laravel\Ai\Streaming\Events\TextStart;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;
use Throwable;

class AssistantController extends Controller
{
    public function stream(Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', '300');
        ignore_user_abort(false);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['sometimes', 'array', 'max:100'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:4000'],
            'session_id' => ['sometimes', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
        ]);

        $sessionId = $validated['session_id'] ?? 'sess_'.Str::uuid();

        config(['ai.providers.opencode.headers.x-opencode-session' => $sessionId]);
        app(AiManager::class)->forgetInstance('opencode');

        $history = collect($validated['history'] ?? [])
            ->take(config('ai.assistant.history_limit', 20))
            ->values()
            ->all();

        $agent = FinanceAssistant::make(
            user: $request->user(),
            history: $history,
        );

        $stream = $agent->stream(
            $validated['message'],
            model: config('ai.assistant.model'),
        );

        $lastWrite = time();
        $currentText = '';
        $currentToolCall = null;

        return response()->stream(function () use ($stream, &$lastWrite, &$currentText, &$currentToolCall) {
            try {
                foreach ($stream as $event) {
                    if (connection_aborted()) {
                        break;
                    }

                    $this->emitHeartbeat($lastWrite);

                    match (true) {
                        $event instanceof TextStart => null,
                        $event instanceof TextDelta => $this->emitTextDelta($event, $currentText),
                        $event instanceof TextEnd => $this->emitTextEnd($currentText),
                        $event instanceof ToolCall => $this->emitToolCall($event, $currentToolCall),
                        $event instanceof ToolResult => $this->emitToolResult($event),
                        $event instanceof Error => $this->emitError($event->message ?? 'حدث خطأ في المعالجة'),
                        $event instanceof StreamStart,
                        $event instanceof StreamEnd,
                        $event instanceof ReasoningStart,
                        $event instanceof ReasoningDelta,
                        $event instanceof ReasoningEnd => null,
                        default => null,
                    };

                    $lastWrite = time();
                }

                $this->emitFrame(['type' => 'done']);
            } catch (Throwable $e) {
                Log::error('Assistant stream failed: '.$e->getMessage(), ['exception' => $e]);
                $this->emitFrame(['type' => 'error', 'message' => 'حدث خطأ في الاتصال بالمساعد، حاول مرة أخرى.']);
                $this->emitFrame(['type' => 'done']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    protected function emitHeartbeat(int &$lastWrite): void
    {
        if (time() - $lastWrite >= 15) {
            echo ": ping\n\n";
            flush();
            $lastWrite = time();
        }
    }

    protected function emitTextDelta(TextDelta $event, string &$currentText): void
    {
        $currentText .= $event->delta;
        $this->emitFrame(['type' => 'text', 'delta' => $event->delta]);
    }

    protected function emitTextEnd(string &$currentText): void
    {
        $currentText = '';
    }

    protected function emitToolCall(ToolCall $event, mixed &$currentToolCall): void
    {
        $currentToolCall = [
            'id' => $event->toolCall->id,
            'name' => $event->toolCall->name,
            'arguments' => $event->toolCall->arguments,
        ];

        $this->emitFrame([
            'type' => 'tool_call',
            'id' => $event->toolCall->id,
            'name' => $event->toolCall->name,
            'arguments' => $event->toolCall->arguments,
        ]);
    }

    protected function emitToolResult(ToolResult $event): void
    {
        $result = json_decode($event->toolResult->result, true) ?? [];

        $this->emitFrame([
            'type' => 'tool_result',
            'id' => $event->toolResult->id,
            'name' => $event->toolResult->name,
            'summary' => $result['summary'] ?? '',
            'ok' => $event->successful && ($result['ok'] ?? false),
            'data' => $result['data'] ?? null,
        ]);
    }

    protected function emitError(string $message): void
    {
        $this->emitFrame(['type' => 'error', 'message' => $message]);
    }

    protected function emitFrame(array $data): void
    {
        echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
        flush();
    }
}
