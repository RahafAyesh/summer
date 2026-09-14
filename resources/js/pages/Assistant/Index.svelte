<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { renderMarkdown } from '@/lib/markdown';

    interface ToolCall {
        id: string;
        name: string;
        arguments: Record<string, any>;
        status: 'running' | 'success' | 'error';
        summary?: string;
        result?: any;
    }

    interface Message {
        id: string;
        role: 'user' | 'assistant';
        content: string;
        toolCalls?: ToolCall[];
        timestamp: number;
    }

    let messages: Message[] = $state([]);
    let input = $state('');
    let isStreaming = $state(false);
    let sessionId = $state(crypto.randomUUID());
    let messagesContainer: HTMLDivElement;
    let abortController: AbortController | null = $state(null);
    let currentAssistantMessage: Message | null = $state(null);

    const examples = [
        'كم صرفت هالشهر؟',
        'أضف 50 ريال قهوة',
        'وش أكبر مصروفي؟',
        'احذف آخر عملية',
    ];

    onMount(() => {
        scrollToBottom();
    });

    $effect(() => {
        if (messages.length > 0) {
            scrollToBottom();
        }
    });

    function scrollToBottom() {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    function newConversation() {
        messages = [];
        sessionId = crypto.randomUUID();
        currentAssistantMessage = null;
    }

    async function sendMessage(content?: string) {
        const messageText = content ?? input.trim();
        if (!messageText || isStreaming) return;

        const userMessage: Message = {
            id: crypto.randomUUID(),
            role: 'user',
            content: messageText,
            timestamp: Date.now(),
        };

        messages = [...messages, userMessage];
        input = '';
        isStreaming = true;

        currentAssistantMessage = {
            id: crypto.randomUUID(),
            role: 'assistant',
            content: '',
            toolCalls: [],
            timestamp: Date.now(),
        };
        messages = [...messages, currentAssistantMessage];

        abortController = new AbortController();

        try {
            const history = messages
                .slice(0, -2)
                .filter((m) => (m.role === 'user' || m.role === 'assistant') && m.content && m.content.trim() !== '')
                .map((m) => ({ role: m.role, content: m.content }))
                .slice(-20);

            const response = await fetch('/assistant/stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    message: messageText,
                    history,
                    session_id: sessionId,
                }),
                signal: abortController.signal,
            });

            if (!response.ok || !(response.headers.get('Content-Type') || '').includes('text/event-stream')) {
                throw new Error(`HTTP ${response.status}`);
            }

            const reader = response.body?.getReader();
            if (!reader) throw new Error('No reader');

            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = line.slice(6);
                        if (data === '[DONE]') continue;

                        try {
                            const frame = JSON.parse(data);
                            handleFrame(frame);
                        } catch (e) {
                            console.error('Parse error:', e);
                        }
                    }
                }
            }
        } catch (error) {
            if (error instanceof Error && error.name === 'AbortError') {
                if (currentAssistantMessage) {
                    currentAssistantMessage.content += '\n\n[تم الإيقاف]';
                    messages = [...messages];
                }
            } else {
                console.error('Stream error:', error);
                if (currentAssistantMessage) {
                    currentAssistantMessage.content += '\n\n[حدث خطأ في الاتصال، حاول مرة أخرى]';
                    messages = [...messages];
                }
            }
        } finally {
            isStreaming = false;
            abortController = null;
            currentAssistantMessage = null;
        }
    }

    function updateCurrentMessage() {
        const current = currentAssistantMessage;
        if (!current) return;
        messages = messages.map((m) => (m.id === current.id ? { ...current } : m));
    }

    function handleFrame(frame: any) {
        if (!currentAssistantMessage) return;

        switch (frame.type) {
            case 'text':
                currentAssistantMessage.content += frame.delta;
                updateCurrentMessage();
                break;

            case 'tool_call':
                currentAssistantMessage.toolCalls = [
                    ...(currentAssistantMessage.toolCalls || []),
                    {
                        id: frame.id,
                        name: frame.name,
                        arguments: frame.arguments,
                        status: 'running',
                    },
                ];
                updateCurrentMessage();
                break;

            case 'tool_result':
                if (currentAssistantMessage.toolCalls) {
                    const tool = currentAssistantMessage.toolCalls.find((t) => t.id === frame.id);
                    if (tool) {
                        tool.status = frame.ok ? 'success' : 'error';
                        tool.summary = frame.summary;
                        tool.result = frame.data;
                        updateCurrentMessage();
                    }
                }
                break;

            case 'error':
                currentAssistantMessage.content += `\n\n[خطأ: ${frame.message}]`;
                updateCurrentMessage();
                break;

            case 'done':
                break;
        }
    }

    function stopStreaming() {
        if (abortController) {
            abortController.abort();
        }
    }

    function getCsrfToken(): string {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function getToolDisplayName(name: string): string {
        const map: Record<string, string> = {
            ListTransactions: 'جاري البحث...',
            CreateTransactions: 'جاري الإضافة...',
            UpdateTransactions: 'جاري التعديل...',
            DeleteTransactions: 'جاري الحذف...',
        };
        return map[name] || name;
    }

    function formatToolArguments(args: Record<string, any>): string {
        return Object.entries(args)
            .map(([key, value]) => `${key}: ${JSON.stringify(value)}`)
            .join(', ');
    }
</script>

<div class="flex h-full flex-col">
    <div class="border-b border-border px-4 py-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">المساعد</h1>
                <p class="text-xs text-muted-foreground">اسألني عن مصاريفك</p>
            </div>
            <button
                onclick={newConversation}
                disabled={isStreaming}
                class="rounded-lg px-3 py-1.5 text-xs text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50 transition-colors"
            >
                محادثة جديدة
            </button>
        </div>
    </div>

    <div bind:this={messagesContainer} class="flex-1 overflow-y-auto px-4 py-6" aria-live="polite">
        <div class="mx-auto max-w-3xl space-y-4">
            {#if messages.length === 0}
                <div class="space-y-4 py-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-lavender/10">
                        <span class="text-2xl">✨</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">أهلاً!</h2>
                        <p class="text-sm text-muted-foreground">اسألني عن مصاريفك أو اطلب إضافة معاملة</p>
                    </div>
                    <div class="mt-6 grid gap-2 sm:grid-cols-2">
                        {#each examples as example}
                            <button
                                onclick={() => sendMessage(example)}
                                class="rounded-xl border border-border p-3 text-right text-sm transition-all hover:border-lavender/50 hover:bg-lavender/5 active:scale-[0.98]"
                            >
                                {example}
                            </button>
                        {/each}
                    </div>
                </div>
            {:else}
                {#each messages as message (message.id)}
                    <div class="flex {message.role === 'user' ? 'justify-end' : 'justify-start'} animate-fade-in">
                        <div
                            class="max-w-[85%] rounded-2xl px-4 py-2.5 {message.role === 'user'
                                ? 'bg-foreground text-background rounded-br-md'
                                : 'bg-muted rounded-bl-md'}"
                        >
                            {#if message.role === 'user'}
                                <p class="whitespace-pre-wrap">{message.content}</p>
                            {:else}
                                {#if message.toolCalls && message.toolCalls.length > 0}
                                    <div class="mb-2 space-y-1.5">
                                        {#each message.toolCalls as tool (tool.id)}
                                            <div class="rounded-lg border border-border bg-background/50 px-3 py-2">
                                                <div class="flex items-center gap-2 text-xs">
                                                    {#if tool.status === 'running'}
                                                        <span class="flex h-2 w-2"><span class="h-2 w-2 animate-ping rounded-full bg-sky opacity-75"></span><span class="h-2 w-2 rounded-full bg-sky"></span></span>
                                                    {:else if tool.status === 'success'}
                                                        <span class="text-mint">✓</span>
                                                    {:else}
                                                        <span class="text-destructive">✗</span>
                                                    {/if}
                                                    <span class="text-muted-foreground">{getToolDisplayName(tool.name)}</span>
                                                </div>
                                                {#if tool.summary}
                                                    <p class="mt-1 text-xs text-muted-foreground">{tool.summary}</p>
                                                {/if}
                                            </div>
                                        {/each}
                                    </div>
                                {/if}
                                {#if message.content}
                                    <div class="md-body prose prose-sm max-w-none dark:prose-invert">
                                        {@html renderMarkdown(message.content)}
                                    </div>
                                {:else if isStreaming && message === currentAssistantMessage}
                                    <div class="flex gap-1 py-1">
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-foreground/40"></span>
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-foreground/40" style="animation-delay: 0.15s"></span>
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-foreground/40" style="animation-delay: 0.3s"></span>
                                    </div>
                                {/if}
                            {/if}
                        </div>
                    </div>
                {/each}
            {/if}
        </div>
    </div>

    <div class="border-t border-border px-4 py-3">
        <div class="mx-auto max-w-3xl">
            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    sendMessage();
                }}
                class="flex gap-2"
            >
                <textarea
                    bind:value={input}
                    placeholder="اسأل أي شيء..."
                    disabled={isStreaming}
                    rows={1}
                    class="flex-1 resize-none rounded-xl border border-border bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-lavender/50 focus:border-lavender/50 disabled:opacity-50 transition-all"
                    onkeydown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            sendMessage();
                        }
                    }}
                ></textarea>
                {#if isStreaming}
                    <button
                        type="button"
                        onclick={stopStreaming}
                        class="rounded-xl bg-destructive px-4 py-3 text-sm text-destructive-foreground hover:bg-destructive/90 transition-all active:scale-95"
                    >
                        إيقاف
                    </button>
                {:else}
                    <button
                        type="submit"
                        disabled={!input.trim()}
                        class="rounded-xl bg-foreground px-4 py-3 text-sm text-background hover:opacity-90 disabled:opacity-30 transition-all active:scale-95"
                    >
                        إرسال
                    </button>
                {/if}
            </form>
        </div>
    </div>
</div>

<style>
    .md-body :global(h1),
    .md-body :global(h2) {
        font-size: 1.125rem;
        font-weight: 600;
    }
    .md-body :global(h3) {
        font-size: 1rem;
        font-weight: 600;
    }
    .md-body :global(h4) {
        font-size: 0.875rem;
        font-weight: 600;
    }
    .md-body :global(p) {
        margin: 0.5rem 0;
    }
    .md-body :global(ul),
    .md-body :global(ol) {
        margin: 0.5rem 0;
        padding-right: 1.5rem;
    }
    .md-body :global(li) {
        margin: 0.25rem 0;
    }
    .md-body :global(table) {
        width: 100%;
        margin: 0.75rem 0;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .md-body :global(th),
    .md-body :global(td) {
        border: 1px solid var(--border);
        padding: 0.5rem;
        text-align: right;
    }
    .md-body :global(th) {
        background: var(--muted);
        font-weight: 600;
    }
    .md-body :global(code) {
        background: var(--muted);
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
    }
    .md-body :global(pre) {
        background: var(--muted);
        padding: 0.75rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin: 0.75rem 0;
    }
    .md-body :global(pre code) {
        background: none;
        padding: 0;
    }
    .md-body :global(blockquote) {
        border-right: 3px solid var(--border);
        padding-right: 1rem;
        margin: 0.75rem 0;
        color: var(--muted-foreground);
    }
    .md-body :global(a) {
        color: var(--primary);
        text-decoration: underline;
    }
    .md-body :global(strong) {
        font-weight: 600;
    }
</style>
