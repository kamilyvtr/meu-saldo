<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the user's financial overview.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $accounts = $user->accounts()
            ->get(['id', 'name', 'type', 'balance', 'is_active']);

        $available = $accounts
            ->filter(fn (Account $account): bool => $account->is_active && $account->type !== AccountType::CreditCard)
            ->sum('balance');

        $cardDebt = max(0, -$accounts
            ->filter(fn (Account $account): bool => $account->is_active && $account->type === AccountType::CreditCard)
            ->sum('balance'));

        $currentMonth = [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ];

        $previousMonth = [
            now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
        ];

        $income = (int) $user->transactions()
            ->whereBetween('date', $currentMonth)
            ->where('type', TransactionType::Income->value)
            ->sum('amount');

        $expense = (int) $user->transactions()
            ->whereBetween('date', $currentMonth)
            ->where('type', TransactionType::Expense->value)
            ->sum('amount');

        $previousIncome = (int) $user->transactions()
            ->whereBetween('date', $previousMonth)
            ->where('type', TransactionType::Income->value)
            ->sum('amount');

        $previousExpense = (int) $user->transactions()
            ->whereBetween('date', $previousMonth)
            ->where('type', TransactionType::Expense->value)
            ->sum('amount');

        $percentageChange = static function (int $current, int $previous): ?float {
            if ($previous === 0) {
                return null;
            }

            return round((($current - $previous) / $previous) * 100, 1);
        };

        $recentTransactions = $user->transactions()
            ->with([
                'account:id,name,type',
                'category:id,name,color,icon',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Transaction $transaction): array => $this->transactionPayload($transaction))
            ->values()
            ->all();

        $chartStart = now()->subDays(29)->startOfDay();

        $groupedTransactions = $user->transactions()
            ->whereBetween('date', [$chartStart->toDateString(), now()->toDateString()])
            ->get(['type', 'amount', 'date'])
            ->groupBy(fn (Transaction $transaction): string => $transaction->date->toDateString());

        $runningIncome = 0;
        $runningExpense = 0;

        $chart = collect(range(29, 0))
            ->map(function (int $daysAgo) use ($groupedTransactions, &$runningIncome, &$runningExpense): array {
                $date = now()->subDays($daysAgo)->startOfDay();
                $dayTransactions = $groupedTransactions->get($date->toDateString(), collect());

                $runningIncome += (int) $dayTransactions
                    ->where('type', TransactionType::Income)
                    ->sum('amount');

                $runningExpense += (int) $dayTransactions
                    ->where('type', TransactionType::Expense)
                    ->sum('amount');

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('d/m'),
                    'income' => $runningIncome,
                    'expense' => $runningExpense,
                    'balance' => $runningIncome - $runningExpense,
                ];
            })
            ->values()
            ->all();

        $currentMonthKey = now()->format('Y-m');

        $budgets = $user->budgets()
            ->with('category:id,name,color,icon')
            ->where('month', $currentMonthKey)
            ->orderBy('id')
            ->get()
            ->map(fn (Budget $budget): array => [
                'id' => $budget->id,
                'category' => $budget->category ? [
                    'id' => $budget->category->id,
                    'name' => $budget->category->name,
                    'color' => $budget->category->color,
                    'icon' => $budget->category->icon,
                ] : null,
                'month' => $budget->month,
                'limit' => $budget->limit_cents,
                'limit_cents' => $budget->limit_cents,
                'spent' => $budget->spentCents(),
                'spent_cents' => $budget->spentCents(),
                'percentage' => $budget->percentage(),
                'is_over' => $budget->isOverBudget(),
            ])
            ->values()
            ->all();

        $goals = $user->goals()
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Goal $goal): array => [
                'id' => $goal->id,
                'name' => $goal->name,
                'target' => $goal->target_cents,
                'target_cents' => $goal->target_cents,
                'current' => $goal->current_cents,
                'current_cents' => $goal->current_cents,
                'progress' => $goal->progressPercentage(),
                'percentage' => $goal->progressPercentage(),
                'completed' => $goal->isCompleted(),
            ])
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'summary' => [
                'available' => (int) $available,
                'card_debt' => (int) $cardDebt,
                'income' => $income,
                'expense' => $expense,
                'savings' => $income - $expense,
                'savings_rate' => $income > 0
                    ? (int) round((($income - $expense) / $income) * 100)
                    : 0,
                'income_change' => $percentageChange($income, $previousIncome),
                'expense_change' => $percentageChange($expense, $previousExpense),
                'accounts_count' => $accounts->count(),
                'active_accounts_count' => $accounts->where('is_active', true)->count(),
                'has_accounts' => $accounts->isNotEmpty(),
            ],
            'recentTransactions' => $recentTransactions,
            'chart' => $chart,
            'budgets' => $budgets,
            'goals' => $goals,
            'monthLabel' => now()->translatedFormat('F \\d\\e Y'),
        ]);
    }

    /**
     * Format a transaction for the dashboard.
     *
     * @return array<string, mixed>
     */
    private function transactionPayload(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'description' => $transaction->description,
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'date' => $transaction->date->toDateString(),
            'date_label' => $transaction->date->translatedFormat('d M'),
            'account' => $transaction->account
                ? [
                    'id' => $transaction->account->id,
                    'name' => $transaction->account->name,
                    'type' => $transaction->account->type->value,
                ]
                : null,
            'category' => $transaction->category
                ? [
                    'id' => $transaction->category->id,
                    'name' => $transaction->category->name,
                    'color' => $transaction->category->color,
                    'icon' => $transaction->category->icon,
                ]
                : null,
        ];
    }
}
