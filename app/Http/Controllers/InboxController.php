<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $user = $request->user();

        $transactions = $user->transactions()
            ->whereNull('category_id')
            ->with([
                'account:id,name,type',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Transaction $transaction): array => [
                'id' => $transaction->id,
                'description' => $transaction->description,
                'type' => $transaction->type->value,
                'amount' => $transaction->amount,
                'date' => $transaction->date->toDateString(),
                'date_label' => $transaction->date->translatedFormat('d M'),
                'account' => $transaction->account ? [
                    'id' => $transaction->account->id,
                    'name' => $transaction->account->name,
                    'type' => $transaction->account->type->value,
                ] : null,
            ]);

        return Inertia::render('inbox/Index', [
            'transactions' => $transactions,
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type', 'is_active'])
                ->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'is_active' => $account->is_active,
                ])->values()->all(),
            'categories' => $user->categories()->orderBy('name')->get(['id', 'name', 'type', 'color', 'icon'])
                ->map(fn ($category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'color' => $category->color,
                    'icon' => $category->icon,
                ])->values()->all(),
        ]);
    }
}
