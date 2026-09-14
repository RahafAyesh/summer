<?php

namespace App\Ai\Tools;

use App\Enums\ExpenseCategory;
use App\Enums\IncomeCategory;
use App\Models\User;
use App\Validation\TransactionRules;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class CreateTransactions implements Tool
{
    public function __construct(protected User $user) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Create transactions for the current user. CRITICAL: Call this tool EXACTLY ONCE per user message. NEVER call it multiple times. '
            .'Batch ALL transactions into a single call. If user says "add 40 SAR expense", create ONE transaction, not three. '
            .'Each item needs: type ("expense" or "income"), amount (positive number, max 2 decimals, SAR), category (expense: '.implode(', ', array_column(ExpenseCategory::cases(), 'value')).'; income: '.implode(', ', array_column(IncomeCategory::cases(), 'value')).')'
            .', date (REQUIRED, literal YYYY-MM-DD you computed yourself — "today"/"yesterday"/"أمس" are INVALID and will be rejected), and optionally description, source, notes. '
            .'Maximum 50 items per call. All items are validated first; if ANY item is invalid nothing is created and errors are returned so you can fix them and retry. '
            .'Returns JSON: {ok, summary, data: {created_ids: [..], created: [{id, type, category, amount, date}]}}.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $transactions = $request['transactions'];

        if (! is_array($transactions) || $transactions === []) {
            return $this->failure('الحقل transactions مطلوب ويجب أن يكون مصفوفة غير فارغة من العمليات.');
        }

        if (count($transactions) > 50) {
            return $this->failure('الحد الأقصى 50 عملية في الاستدعاء الواحد، تم رفض الطلب بدون أي إنشاء.');
        }

        $validatedItems = [];
        $errors = [];

        foreach (array_values($transactions) as $index => $item) {
            if (! is_array($item)) {
                $errors["transactions.{$index}"] = ['كل عنصر يجب أن يكون كائناً.'];

                continue;
            }

            try {
                $validatedItems[$index] = Validator::validate($item, TransactionRules::forType(
                    Arr::get($item, 'type'),
                    strictDate: true,
                ));
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    $errors["transactions.{$index}.{$field}"] = $messages;
                }
            }
        }

        if ($errors !== []) {
            return json_encode([
                'ok' => false,
                'summary' => 'لم تُنشأ أي عملية — صحّح الأخطاء التالية وأعد المحاولة.',
                'data' => ['errors' => $errors],
            ], JSON_UNESCAPED_UNICODE);
        }

        try {
            $created = DB::transaction(function () use ($validatedItems) {
                $rows = [];

                foreach ($validatedItems as $validated) {
                    $fields = Arr::only(
                        $validated,
                        ['type', 'amount', 'category', 'description', 'source', 'notes', 'date'],
                    );
                    $fields['date'] = Carbon::parse($fields['date'])->toDateString();

                    $transaction = $this->user->transactions()->create($fields);

                    $rows[] = [
                        'id' => $transaction->id,
                        'type' => $transaction->type->value,
                        'category' => $transaction->category,
                        'amount' => $transaction->amount,
                        'date' => $transaction->date->format('Y-m-d'),
                    ];
                }

                return $rows;
            });
        } catch (Throwable $e) {
            report($e);

            return $this->failure('فشل إنشاء العمليات بعد التحقق، لم يُحفظ أي سجل.');
        }

        return json_encode([
            'ok' => true,
            'summary' => 'أضيفت '.count($created).' عملية بنجاح.',
            'data' => [
                'created_ids' => array_column($created, 'id'),
                'created' => $created,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function failure(string $summary): string
    {
        return json_encode([
            'ok' => false,
            'summary' => $summary,
            'data' => [],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        $expenseCategories = array_column(ExpenseCategory::cases(), 'value');
        $incomeCategories = array_column(IncomeCategory::cases(), 'value');
        $allCategories = array_values(array_unique([...$expenseCategories, ...$incomeCategories]));

        return [
            'transactions' => $schema->array()
                ->items($schema->object(fn (JsonSchema $s) => [
                    'type' => $s->string()->enum(['expense', 'income'])->required(),
                    'amount' => $s->number()->min(0.01)->required()
                        ->description('Amount in SAR, positive, max 2 decimal places'),
                    'category' => $s->string()->enum($allCategories)->required()
                        ->description('Expense categories: '.implode(', ', $expenseCategories).'. Income categories: '.implode(', ', $incomeCategories).'.'),
                    'date' => $s->string()
                        ->pattern('^\d{4}-\d{2}-\d{2}$')
                        ->required()
                        ->description('MUST be literal YYYY-MM-DD computed from today\'s date in the instructions. Relative words (today, yesterday, أمس) are forbidden and rejected.'),
                    'description' => $s->string()->max(255)->nullable(),
                    'source' => $s->string()->max(255)->nullable()
                        ->description('Optional income source label'),
                    'notes' => $s->string()->max(1000)->nullable(),
                ]))
                ->min(1)
                ->max(50)
                ->required()
                ->description('Transactions to create, 1-50 items'),
        ];
    }
}
