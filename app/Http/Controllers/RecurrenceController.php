<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecurrenceRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Recurrence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurrenceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Recurrence::class);

        $user = $request->user();

        $recurrences = $user->recurrences()
            ->with(['category:id,name,type,color,icon', 'account:id,name,type,color'])
            ->orderBy('next_run_date')
            ->get()
            ->map(fn (Recurrence $recurrence): array => $this->recurrencePayload($recurrence))
            ->values()
            ->all();

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'color'])
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'color' => $account->color,
            ])
            ->values()
            ->all();

        $categories = $user->categories()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'color', 'icon'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
                'color' => $category->color,
                'icon' => $category->icon,
            ])
            ->values()
            ->all();

        return Inertia::render('recurrences/Index', [
            'recurrences' => $recurrences,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    public function store(RecurrenceRequest $request): RedirectResponse
    {
        $this->authorize('create', Recurrence::class);

        $data = $request->validated();

        $request->user()->recurrences()->create([
            'description' => $data['description'],
            'amount_cents' => $data['amount_cents'],
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'frequency' => $data['frequency'],
            'next_run_date' => $data['next_run_date'],
            'end_date' => $data['end_date'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Recorrência criada com sucesso.',
        ]);

        return back();
    }

    public function update(RecurrenceRequest $request, Recurrence $recurrence): RedirectResponse
    {
        $this->authorize('update', $recurrence);

        $data = $request->validated();

        $recurrence->update([
            'description' => $data['description'],
            'amount_cents' => $data['amount_cents'],
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'frequency' => $data['frequency'],
            'next_run_date' => $data['next_run_date'],
            'end_date' => $data['end_date'] ?? null,
            'is_active' => $data['is_active'] ?? $recurrence->is_active,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Recorrência atualizada com sucesso.',
        ]);

        return back();
    }

    public function destroy(Recurrence $recurrence): RedirectResponse
    {
        $this->authorize('delete', $recurrence);

        $recurrence->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Recorrência removida.',
        ]);

        return back();
    }

    public function toggle(Recurrence $recurrence): RedirectResponse
    {
        $this->authorize('update', $recurrence);

        $recurrence->update(['is_active' => ! $recurrence->is_active]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $recurrence->is_active ? 'Recorrência ativada.' : 'Recorrência pausada.',
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function recurrencePayload(Recurrence $recurrence): array
    {
        return [
            'id' => $recurrence->id,
            'description' => $recurrence->description,
            'amount' => $recurrence->amount_cents,
            'amount_cents' => $recurrence->amount_cents,
            'type' => $recurrence->type->value,
            'category_id' => $recurrence->category_id,
            'category' => $recurrence->category ? [
                'id' => $recurrence->category->id,
                'name' => $recurrence->category->name,
                'type' => $recurrence->category->type->value,
                'color' => $recurrence->category->color,
                'icon' => $recurrence->category->icon,
            ] : null,
            'account_id' => $recurrence->account_id,
            'account' => $recurrence->account ? [
                'id' => $recurrence->account->id,
                'name' => $recurrence->account->name,
                'type' => $recurrence->account->type->value,
                'color' => $recurrence->account->color,
            ] : null,
            'frequency' => $recurrence->frequency->value,
            'next_run_date' => $recurrence->next_run_date->toDateString(),
            'end_date' => $recurrence->end_date?->toDateString(),
            'is_active' => $recurrence->is_active,
        ];
    }
}
