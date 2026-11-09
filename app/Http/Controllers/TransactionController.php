<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\TransactionRequest;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Show the user's transactions.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $user = $request->user();

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'type' => (string) $request->query('type', ''),
            'account_id' => (string) $request->query('account_id', ''),
            'category_id' => (string) $request->query('category_id', ''),
            'month' => (string) $request->query('month', ''),
        ];

        if (preg_match('/^\d{4}-\d{2}$/', $filters['month']) !== 1) {
            $filters['month'] = '';
        }

        $query = $user->transactions()
            ->with([
                'account:id,name,type',
                'category:id,name,color,icon',
            ])
            ->when($filters['search'] !== '', fn ($query) => $query->where('description', 'like', "%{$filters['search']}%"))
            ->when(
                in_array($filters['type'], [TransactionType::Income->value, TransactionType::Expense->value], true),
                fn ($query) => $query->where('type', $filters['type']),
            )
            ->when(ctype_digit($filters['account_id']), fn ($query) => $query->where('account_id', (int) $filters['account_id']))
            ->when(ctype_digit($filters['category_id']), fn ($query) => $query->where('category_id', (int) $filters['category_id']))
            ->when($filters['month'] !== '', function ($query) use ($filters): void {
                $start = Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth();

                $query->whereBetween('date', [
                    $start->toDateString(),
                    $start->copy()->endOfMonth()->toDateString(),
                ]);
            });

        $summary = [
            'income' => (int) (clone $query)->where('type', TransactionType::Income->value)->sum('amount'),
            'expense' => (int) (clone $query)->where('type', TransactionType::Expense->value)->sum('amount'),
        ];
        $summary['balance'] = $summary['income'] - $summary['expense'];

        $transactions = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Transaction $transaction): array => $this->transactionPayload($transaction));

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'summary' => $summary,
            'accounts' => $user->accounts()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'is_active'])
                ->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'is_active' => $account->is_active,
                ])
                ->values()
                ->all(),
            'categories' => $user->categories()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color', 'icon'])
                ->map(fn ($category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'color' => $category->color,
                    'icon' => $category->icon,
                ])
                ->values()
                ->all(),
            'months' => collect(range(0, 11))
                ->map(function (int $monthsAgo): array {
                    $date = now()->subMonthsNoOverflow($monthsAgo);

                    return [
                        'value' => $date->format('Y-m'),
                        'label' => ucfirst($date->translatedFormat('F Y')),
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a new transaction.
     */
    public function store(TransactionRequest $request): RedirectResponse
    {
        $this->authorize('create', Transaction::class);

        $data = $request->validated();
        $account = $request->user()->accounts()->whereKey($data['account_id'])->firstOrFail();

        DB::transaction(function () use ($request, $data, $account): void {
            $request->user()->transactions()->create([
                'account_id' => $account->id,
                'category_id' => $data['category_id'] ?? null,
                'description' => $data['description'],
                'type' => $data['type'],
                'amount' => $data['amount_cents'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->applyToBalance($account, TransactionType::from($data['type']), $data['amount_cents'], 1);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Transação registrada com sucesso.',
        ]);

        return back();
    }

    /**
     * Update an existing transaction.
     */
    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $data = $request->validated();
        $newAccount = $request->user()->accounts()->whereKey($data['account_id'])->firstOrFail();

        DB::transaction(function () use ($transaction, $data, $newAccount): void {
            $previousAccount = $transaction->account()->firstOrFail();

            $this->applyToBalance($previousAccount, $transaction->type, $transaction->amount, -1);

            $transaction->update([
                'account_id' => $newAccount->id,
                'category_id' => $data['category_id'] ?? null,
                'description' => $data['description'],
                'type' => $data['type'],
                'amount' => $data['amount_cents'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->applyToBalance($newAccount, TransactionType::from($data['type']), $data['amount_cents'], 1);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Transação atualizada com sucesso.',
        ]);

        return back();
    }

    /**
     * Assign a category to a transaction (used by the inbox).
     */
    public function categorize(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
        ]);

        $categoryId = $validated['category_id'] ?? null;

        if ($categoryId !== null) {
            $exists = $request->user()->categories()->whereKey($categoryId)->exists();

            if (! $exists) {
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'Categoria inválida.',
                ]);

                return back();
            }
        }

        $transaction->update(['category_id' => $categoryId]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Categoria atualizada.',
        ]);

        return back();
    }

    /**
     * Delete a transaction.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        DB::transaction(function () use ($transaction): void {
            $account = $transaction->account()->first();

            if ($account instanceof Account) {
                $this->applyToBalance($account, $transaction->type, $transaction->amount, -1);
            }

            $transaction->delete();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Transação removida.',
        ]);

        return back();
    }

    /**
     * Apply (or revert) a transaction amount to an account balance.
     */
    private function applyToBalance(Account $account, TransactionType $type, int $amount, int $direction): void
    {
        $delta = $type === TransactionType::Income ? $amount : -$amount;

        $account->increment('balance', $delta * $direction);
    }

    /**
     * Format a transaction for the front-end.
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
            'notes' => $transaction->notes,
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
