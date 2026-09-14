<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $currentMonth = $request->get('month', now()->format('Y-m'));

        $totalIncome = Transaction::forUser($user)->inMonth($currentMonth)->income()->sum('amount');
        $totalExpenses = Transaction::forUser($user)->inMonth($currentMonth)->expense()->sum('amount');

        $netBalance = $totalIncome - $totalExpenses;

        $savingsRate = $totalIncome > 0
            ? round((($totalIncome - $totalExpenses) / $totalIncome) * 100)
            : 0;

        $expensesByCategory = Transaction::forUser($user)
            ->inMonth($currentMonth)
            ->expense()
            ->select('category')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->sortDesc();

        $recentTransactions = Transaction::forUser($user)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $budgets = $user->budgets()->forMonth($currentMonth)->get();

        $budgetAlerts = $budgets->filter(function ($budget) use ($expensesByCategory) {
            $spent = $expensesByCategory[$budget->category->value] ?? 0;

            return $spent >= ($budget->amount * 0.75);
        })->count();

        $transactionCount = Transaction::forUser($user)->inMonth($currentMonth)->count();

        return Inertia::render('Dashboard', [
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'netBalance' => $netBalance,
            'savingsRate' => $savingsRate,
            'expensesByCategory' => $expensesByCategory,
            'recentTransactions' => $recentTransactions,
            'budgetAlerts' => $budgetAlerts,
            'transactionCount' => $transactionCount,
            'currentMonth' => $currentMonth,
        ]);
    }
}
