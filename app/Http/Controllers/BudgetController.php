<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        $budgets = $request->user()
            ->budgets()
            ->forMonth($month)
            ->get();

        $expenses = Transaction::forUser($request->user())
            ->inMonth($month)
            ->expense()
            ->select('category')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgets,
            'expenses' => $expenses,
            'currentMonth' => $month,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(array_column(ExpenseCategory::cases(), 'value'))],
            'amount' => ['required', 'numeric', 'min:0'],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $request->user()->budgets()->updateOrCreate(
            [
                'category' => $validated['category'],
                'month' => $validated['month'],
            ],
            [
                'amount' => $validated['amount'],
            ]
        );

        return redirect()->back()->with('success', 'تم حفظ الميزانية بنجاح');
    }

    public function destroy(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $budget->delete();

        return redirect()->back()->with('success', 'تم حذف الميزانية بنجاح');
    }
}
