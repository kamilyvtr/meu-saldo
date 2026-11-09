<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoalRequest;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Goal::class);

        $user = $request->user();

        $goals = $user->goals()
            ->with('account:id,name,type,color')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Goal $goal): array => $this->goalPayload($goal))
            ->values()
            ->all();

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'balance', 'color'])
            ->map(fn ($account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'balance' => $account->balance,
                'color' => $account->color,
            ])
            ->values()
            ->all();

        return Inertia::render('goals/Index', [
            'goals' => $goals,
            'accounts' => $accounts,
        ]);
    }

    public function store(GoalRequest $request): RedirectResponse
    {
        $this->authorize('create', Goal::class);

        $data = $request->validated();

        $request->user()->goals()->create([
            'name' => $data['name'],
            'target_cents' => $data['target_cents'],
            'current_cents' => $data['current_cents'] ?? 0,
            'deadline' => $data['deadline'] ?? null,
            'account_id' => $data['account_id'] ?? null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Meta criada com sucesso.',
        ]);

        return back();
    }

    public function update(GoalRequest $request, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);

        $data = $request->validated();

        $goal->update([
            'name' => $data['name'],
            'target_cents' => $data['target_cents'],
            'current_cents' => $data['current_cents'] ?? $goal->current_cents,
            'deadline' => $data['deadline'] ?? null,
            'account_id' => $data['account_id'] ?? null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Meta atualizada com sucesso.',
        ]);

        return back();
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $this->authorize('delete', $goal);

        $goal->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Meta removida.',
        ]);

        return back();
    }

    public function deposit(Request $request, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);

        $amount = $this->extractAmount($request);

        $goal->deposit($amount);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Dinheiro guardado na meta.',
        ]);

        return back();
    }

    public function withdraw(Request $request, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);

        $amount = $this->extractAmount($request);

        $goal->withdraw($amount);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Dinheiro resgatado da meta.',
        ]);

        return back();
    }

    private function extractAmount(Request $request): int
    {
        foreach (['amount_cents', 'amount', 'value_cents', 'value'] as $key) {
            if ($request->has($key)) {
                return max(0, (int) $request->input($key));
            }
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function goalPayload(Goal $goal): array
    {
        return [
            'id' => $goal->id,
            'name' => $goal->name,
            'target' => $goal->target_cents,
            'target_cents' => $goal->target_cents,
            'current' => $goal->current_cents,
            'current_cents' => $goal->current_cents,
            'deadline' => $goal->deadline?->toDateString(),
            'account_id' => $goal->account_id,
            'account' => $goal->account ? [
                'id' => $goal->account->id,
                'name' => $goal->account->name,
                'type' => $goal->account->type->value,
                'color' => $goal->account->color,
            ] : null,
            'progress' => $goal->progressPercentage(),
            'percentage' => $goal->progressPercentage(),
            'progress_percentage' => $goal->progressPercentage(),
            'completed' => $goal->isCompleted(),
            'is_completed' => $goal->isCompleted(),
            'remaining' => $goal->remainingCents(),
            'remaining_cents' => $goal->remainingCents(),
        ];
    }
}
