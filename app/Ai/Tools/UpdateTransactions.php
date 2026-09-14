<?php

namespace App\Ai\Tools;

use App\Enums\ExpenseCategory;
use App\Enums\IncomeCategory;
use App\Models\Transaction;
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

class UpdateTransactions implements Tool
{
    public function __construct(protected User $user) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Update one or MORE of the current user\'s own transactions in a single bulk call. PARTIAL update only: include the id plus ONLY the fields that must change; omitted fields keep their current values. '
            .'Valid fields: type (income/expense), amount (positive, max 2 decimals), category (expense: '.implode(', ', array_column(ExpenseCategory::cases(), 'value')).'; income: '.implode(', ', array_column(IncomeCategory::cases(), 'value')).'), date (literal YYYY-MM-DD — relative words are rejected), description, source, notes. '
            .'IDs must come from a ListTransactions result — NEVER guess an ID; an id the user does not own is ignored and reported in not_found. ALWAYS list first to obtain real ids. '
            .'Maximum 50 items. All items are validated first; if ANY item is invalid nothing is written and errors are returned so you can fix and retry. '
            .'Returns JSON: {ok, summary, data: {updated: [{id, changed: {...}}], not_found: [ids]}}.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $updates = $request['updates'];

        if (! is_array($updates) || $updates === []) {
            return $this->failure('الحقل updates مطلوب ويجب أن يكون مصفوفة غير فارغة.');
        }

        if (count($updates) > 50) {
            return $this->failure('الحد الأقصى 50 عنصراً في الاستدعاء الواحد، لم يُعدَّل أي سجل.');
        }

        $errors = [];
        $prepared = [];

        foreach (array_values($updates) as $index => $item) {
            if (! is_array($item)) {
                $errors["updates.{$index}"] = ['كل عنصر يجب أن يكون كائناً.'];

                continue;
            }

            try {
                $validated = Validator::validate($item, [
                    'id' => ['required', 'integer'],
                    ...TransactionRules::partial(),
                ]);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    $errors["updates.{$index}.{$field}"] = $messages;
                }

                continue;
            }

            $fields = Arr::except($validated, ['id']);

            if ($fields === []) {
                $errors["updates.{$index}"] = ['لم يُرسل أي حقل لتعديله — أرسل حقلاً واحداً على الأقل مع المعرّف.'];

                continue;
            }

            $prepared[$index] = ['id' => $validated['id'], 'fields' => $fields];
        }

        if ($errors !== []) {
            return json_encode([
                'ok' => false,
                'summary' => 'لم يُعدَّل أي سجل — صحّح الأخطاء وأعد المحاولة.',
                'data' => ['errors' => $errors],
            ], JSON_UNESCAPED_UNICODE);
        }

        $ids = array_column($prepared, 'id');
        $owned = $this->user->transactions()->whereIn('id', $ids)->get()->keyBy('id');

        $notFound = array_values(array_unique(array_diff($ids, $owned->keys()->all())));

        foreach ($prepared as $index => $entry) {
            /** @var Transaction|null $transaction */
            $transaction = $owned->get($entry['id']);

            if (! $transaction) {
                continue;
            }

            $finalType = Arr::get($entry['fields'], 'type', $transaction->type->value);
            $finalCategory = Arr::get($entry['fields'], 'category', $transaction->category);

            if (! in_array($finalCategory, TransactionRules::allowedCategories($finalType), true)) {
                $errors["updates.{$index}.category"] = [
                    "التصنيف {$finalCategory} غير مسموح لنوع {$finalType}. المسموح لهذا النوع: "
                    .implode(', ', TransactionRules::allowedCategories($finalType)),
                ];
            }
        }

        if ($errors !== []) {
            return json_encode([
                'ok' => false,
                'summary' => 'لم يُعدَّل أي سجل — صحّح الأخطاء وأعد المحاولة.',
                'data' => [
                    'errors' => $errors,
                    'not_found' => $notFound,
                ],
            ], JSON_UNESCAPED_UNICODE);
        }

        try {
            $updated = DB::transaction(function () use ($prepared, $owned) {
                $rows = [];

                foreach ($prepared as $entry) {
                    $transaction = $owned->get($entry['id']);

                    if (! $transaction) {
                        continue;
                    }

                    $fields = $entry['fields'];

                    if (Arr::has($fields, 'date')) {
                        $fields['date'] = Carbon::parse($fields['date'])->toDateString();
                    }

                    $transaction->fill($fields)->save();

                    $rows[] = [
                        'id' => $transaction->id,
                        'changed' => $fields,
                    ];
                }

                return $rows;
            });
        } catch (Throwable $e) {
            report($e);

            return $this->failure('فشل التعديل، لم يُحفظ أي تغيير.');
        }

        return json_encode([
            'ok' => true,
            'summary' => 'عُدِّلت '.count($updated).' عملية'
                .($notFound !== [] ? '، وتُجوهل '.count($notFound).' معرّفاً غير مملوك للمستخدم.' : '.'),
            'data' => [
                'updated' => $updated,
                'not_found' => $notFound,
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
            'updates' => $schema->array()
                ->items($schema->object(fn (JsonSchema $s) => [
                    'id' => $s->integer()->required()
                        ->description('Existing transaction id owned by the current user (from ListTransactions)'),
                    'type' => $s->string()->enum(['expense', 'income'])->nullable(),
                    'amount' => $s->number()->min(0.01)->nullable(),
                    'category' => $s->string()->enum($allCategories)->nullable()
                        ->description('Must be valid for the transaction\'s (new) type'),
                    'date' => $s->string()
                        ->pattern('^\d{4}-\d{2}-\d{2}$')
                        ->nullable()
                        ->description('Literal YYYY-MM-DD only — relative words are forbidden'),
                    'description' => $s->string()->max(255)->nullable(),
                    'source' => $s->string()->max(255)->nullable(),
                    'notes' => $s->string()->max(1000)->nullable(),
                ]))
                ->min(1)
                ->max(50)
                ->required()
                ->description('Updates to apply, 1-50 items, partial (only listed fields change)'),
        ];
    }
}
