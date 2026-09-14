<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class DeleteTransactions implements Tool
{
    public function __construct(protected User $user) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Delete (soft-delete) one or MORE of the current user\'s own transactions by EXPLICIT numeric ids in a single bulk call. '
            .'IDs are mandatory — there is NO "delete all" and NO filter-based deletion. You MUST call ListTransactions first to obtain the exact ids the user owns; NEVER invent ids. '
            .'Any id the user does not own is skipped and reported in not_found. Maximum 50 ids per call. '
            .'Returns JSON: {ok, summary, data: {deleted_count, deleted_ids: [..], not_found: [ids]}}. '
            .'Use this only after the user has clearly confirmed they want these specific records deleted.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $ids = $request['ids'];

        if (! is_array($ids) || $ids === []) {
            return $this->failure('الحقل ids مطلوب ويجب أن يكون مصفوفة غير فارغة من المعرّفات الرقمية. لا يمكن حذف كل العمليات دفعة واحدة.');
        }

        try {
            $validated = Validator::validate(['ids' => $ids], [
                'ids' => ['required', 'array', 'min:1', 'max:50'],
                'ids.*' => ['required', 'integer'],
            ]);
        } catch (ValidationException $e) {
            return json_encode([
                'ok' => false,
                'summary' => 'لم يُحذف أي سجل — صحّح المعرّفات وأعد المحاولة.',
                'data' => ['errors' => $e->errors()],
            ], JSON_UNESCAPED_UNICODE);
        }

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $owned = $this->user->transactions()->whereIn('id', $ids)->pluck('id')->all();
        $notFound = array_values(array_diff($ids, $owned));

        try {
            $deletedIds = DB::transaction(function () use ($owned) {
                $this->user->transactions()->whereIn('id', $owned)->delete();

                return $owned;
            });
        } catch (Throwable $e) {
            report($e);

            return $this->failure('فشل الحذف، لم يُحذف أي سجل.');
        }

        return json_encode([
            'ok' => true,
            'summary' => 'حُذفت '.count($deletedIds).' عملية'
                .($notFound !== [] ? '، وتُجوهل '.count($notFound).' معرّفاً غير مملوك للمستخدم.' : '.'),
            'data' => [
                'deleted_count' => count($deletedIds),
                'deleted_ids' => $deletedIds,
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
        return [
            'ids' => $schema->array()
                ->items($schema->integer())
                ->min(1)
                ->max(50)
                ->required()
                ->description('Explicit numeric transaction ids to delete (from ListTransactions); 1-50 items'),
        ];
    }
}
