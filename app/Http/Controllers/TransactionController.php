<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Validation\TransactionRules;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::forUser($request->user());

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('month')) {
            $query->inMonth($request->input('month'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => [
                'type' => $request->input('type'),
                'month' => $request->input('month'),
                'search' => $request->input('search'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            TransactionRules::forType($request->input('type'))
        );

        $request->user()->transactions()->create($validated);

        return redirect()->back()->with('success', 'تمت إضافة المعاملة بنجاح');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $validated = $request->validate(
            TransactionRules::forType($request->input('type'))
        );

        $transaction->update($validated);

        return redirect()->back()->with('success', 'تم تحديث المعاملة بنجاح');
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return redirect()->back()->with('success', 'تم حذف المعاملة بنجاح');
    }
}
