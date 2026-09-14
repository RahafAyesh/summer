<?php

namespace App\Ai\Tools;

use App\Enums\ExpenseCategory;
use App\Enums\IncomeCategory;
use App\Models\User;
use App\Validation\TransactionRules;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListTransactions implements Tool
{
    public function __construct(protected User $user) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Query the CURRENT signed-in user\'s own income/expense transactions with optional filters, sorted results, a total count and the summed amount of ALL matches (even beyond the returned page). '
            .'Use this to answer questions like "how much did I spend on food last month?", to list recent transactions, and ALWAYS call it before UpdateTransactions or DeleteTransactions when the user describes records by words instead of IDs. '
            .'Every date filter MUST be a literal YYYY-MM-DD string computed from the current date given in the instructions — relative expressions ("today", "yesterday", "last month") are invalid. '
            .'Filters: type is "expense" or "income"; category is one allowed value or an array of them; min_amount/max_amount are positive numbers; search matches description/notes/source text; sort is date_desc (default), date_asc, amount_desc or amount_asc; limit is 1-100 (default 25). '
            .'Returns JSON: {ok, summary, data: {transactions: [{id, date, type, category, amount, description}], total_count, sum_amount, returned_count, truncated}}. '
            .'When truncated is true the data contains only a page — say so instead of treating it as the full list.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        try {
            $validated = Validator::validate($request->all(), [
                'type' => ['sometimes', 'in:income,expense'],
                'category' => ['sometimes'],
                'date_from' => ['sometimes', 'date_format:Y-m-d'],
                'date_to' => ['sometimes', 'date_format:Y-m-d'],
                'min_amount' => ['sometimes', 'numeric', 'min:0'],
                'max_amount' => ['sometimes', 'numeric', 'min:0'],
                'search' => ['sometimes', 'string', 'max:255'],
                'sort' => ['sometimes', 'in:date_desc,date_asc,amount_desc,amount_asc'],
                'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);
        } catch (ValidationException $e) {
            return json_encode([
                'ok' => false,
                'summary' => 'مدخلات غير صالحة، صحّحها وأعد المحاولة.',
                'data' => ['errors' => $e->errors()],
            ], JSON_UNESCAPED_UNICODE);
        }

        $categories = $validated['category'] ?? null;

        if ($categories !== null) {
            $categories = is_array($categories) ? $categories : [$categories];
            $allowed = TransactionRules::allowedCategories($validated['type'] ?? null);

            if ($bad = array_values(array_diff($categories, $allowed))) {
                return json_encode([
                    'ok' => false,
                    'summary' => 'تصنيفات غير معروفة: '.implode(', ', $bad),
                    'data' => [
                        'invalid_categories' => $bad,
                        'allowed_categories' => $allowed,
                    ],
                ], JSON_UNESCAPED_UNICODE);
            }
        }

        $sort = $validated['sort'] ?? 'date_desc';

        $query = $this->user->transactions()
            ->when($validated['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($categories, fn ($q) => $q->whereIn('category', $categories))
            ->when($validated['date_from'] ?? null, fn ($q, $from) => $q->whereDate('date', '>=', $from))
            ->when($validated['date_to'] ?? null, fn ($q, $to) => $q->whereDate('date', '<=', $to))
            ->when($validated['min_amount'] ?? null, fn ($q, $min) => $q->where('amount', '>=', $min))
            ->when($validated['max_amount'] ?? null, fn ($q, $max) => $q->where('amount', '<=', $max))
            ->when($validated['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%");
                });
            });

        $totalCount = (clone $query)->count();
        $sumAmount = number_format((float) (clone $query)->sum('amount'), 2, '.', '');

        $limit = $validated['limit'] ?? 25;

        $sorted = match ($sort) {
            'date_asc' => (clone $query)->orderBy('date')->orderBy('id'),
            'amount_desc' => (clone $query)->orderByDesc('amount')->orderByDesc('date'),
            'amount_asc' => (clone $query)->orderBy('amount')->orderBy('date'),
            default => (clone $query)->orderByDesc('date')->orderByDesc('id'),
        };

        $rows = $sorted
            ->limit($limit)
            ->get(['id', 'type', 'amount', 'category', 'description', 'date']);

        $transactions = $rows->map(fn ($t): array => [
            'id' => $t->id,
            'date' => $t->date->format('Y-m-d'),
            'type' => $t->type->value,
            'category' => $t->category,
            'amount' => $t->amount,
            'description' => $t->description,
        ])->all();

        return json_encode([
            'ok' => true,
            'summary' => 'عرض '.count($transactions)." من أصل {$totalCount} عملية — مجموع كل المطابقات {$sumAmount} ريال.",
            'data' => [
                'transactions' => $transactions,
                'total_count' => $totalCount,
                'sum_amount' => $sumAmount,
                'returned_count' => count($transactions),
                'truncated' => $totalCount > count($transactions),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        $categories = array_values(array_unique([
            ...array_column(ExpenseCategory::cases(), 'value'),
            ...array_column(IncomeCategory::cases(), 'value'),
        ]));

        return [
            'date_from' => $schema->string()
                ->pattern('^\d{4}-\d{2}-\d{2}$')
                ->description('Inclusive start date, literal YYYY-MM-DD (never a relative expression like "today")')
                ->nullable(),
            'date_to' => $schema->string()
                ->pattern('^\d{4}-\d{2}-\d{2}$')
                ->description('Inclusive end date, literal YYYY-MM-DD (never relative)')
                ->nullable(),
            'type' => $schema->string()
                ->enum(['income', 'expense'])
                ->description('Filter by transaction type')
                ->nullable(),
            'category' => $schema->array()
                ->items($schema->string()->enum($categories))
                ->description('Filter by one or more category values')
                ->nullable(),
            'min_amount' => $schema->number()
                ->min(0)
                ->description('Minimum amount (SAR, inclusive)')
                ->nullable(),
            'max_amount' => $schema->number()
                ->min(0)
                ->description('Maximum amount (SAR, inclusive)')
                ->nullable(),
            'search' => $schema->string()
                ->max(255)
                ->description('Text search in description/notes/source')
                ->nullable(),
            'sort' => $schema->string()
                ->enum(['date_desc', 'date_asc', 'amount_desc', 'amount_asc'])
                ->description('Sort order, default date_desc')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Max transactions to return, default 25, max 100')
                ->nullable(),
        ];
    }
}
