<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\AccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Show the user's accounts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Account::class);

        $user = $request->user();

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'type' => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', 'all'),
        ];

        $accounts = $user->accounts()
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query->where('name', 'like', "%{$filters['search']}%")
                        ->orWhere('institution', 'like', "%{$filters['search']}%");
                });
            })
            ->when($filters['type'] !== '', fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['status'] === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (Account $account): array => $this->accountPayload($account))
            ->values()
            ->all();

        $allAccounts = $user->accounts()->get(['type', 'balance', 'is_active']);

        return Inertia::render('accounts/Index', [
            'accounts' => $accounts,
            'filters' => $filters,
            'types' => collect(AccountType::cases())
                ->map(fn (AccountType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->all(),
            'summary' => [
                'available' => (int) $allAccounts
                    ->filter(fn (Account $account): bool => $account->is_active && $account->type !== AccountType::CreditCard)
                    ->sum('balance'),
                'card_debt' => max(0, -(int) $allAccounts
                    ->filter(fn (Account $account): bool => $account->is_active && $account->type === AccountType::CreditCard)
                    ->sum('balance')),
                'active_count' => $allAccounts->where('is_active', true)->count(),
                'total_count' => $allAccounts->count(),
            ],
        ]);
    }

    /**
     * Store a new account.
     */
    public function store(AccountRequest $request): RedirectResponse
    {
        $this->authorize('create', Account::class);

        $data = $request->validated();

        $request->user()->accounts()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'balance' => $data['balance_cents'],
            'color' => $data['color'] ?? null,
            'institution' => $data['institution'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Conta criada com sucesso.',
        ]);

        return back();
    }

    /**
     * Update an existing account.
     */
    public function update(AccountRequest $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $data = $request->validated();

        $account->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'balance' => $data['balance_cents'],
            'color' => $data['color'] ?? null,
            'institution' => $data['institution'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Conta atualizada com sucesso.',
        ]);

        return back();
    }

    /**
     * Delete an account.
     */
    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Conta removida.',
        ]);

        return back();
    }

    /**
     * Format an account for the front-end.
     *
     * @return array<string, mixed>
     */
    private function accountPayload(Account $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type->value,
            'type_label' => $account->type->label(),
            'balance' => $account->balance,
            'color' => $account->color,
            'institution' => $account->institution,
            'is_active' => $account->is_active,
        ];
    }
}
