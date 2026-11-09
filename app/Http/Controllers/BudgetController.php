<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Budget::class);

        $user = $request->user();

        $month = (string) $request->query('month', now()->format('Y-m'));

        $budgets = $user->budgets()
            ->with('category:id,name,type,color,icon')
            ->when($month !== '', fn ($query) => $query->where('month', $month))
            ->orderBy('id')
            ->get()
            ->map(fn (Budget $budget): array => $this->budgetPayload($budget))
            ->values()
            ->all();

        $categories = $user->categories()
            ->where('type', 'expense')
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

        return Inertia::render('budgets/Index', [
            'budgets' => $budgets,
            'categories' => $categories,
            'month' => $month,
        ]);
    }

    public function store(BudgetRequest $request): RedirectResponse
    {
        $this->authorize('create', Budget::class);

        $data = $request->validated();

        $request->user()->budgets()->create([
            'category_id' => $data['category_id'],
            'month' => $data['month'],
            'limit_cents' => $data['limit_cents'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Orçamento criado com sucesso.',
        ]);

        return back();
    }

    public function update(BudgetRequest $request, Budget $budget): RedirectResponse
    {
        $this->authorize('update', $budget);

        $data = $request->validated();

        $budget->update([
            'category_id' => $data['category_id'],
            'month' => $data['month'],
            'limit_cents' => $data['limit_cents'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Orçamento atualizado com sucesso.',
        ]);

        return back();
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorize('delete', $budget);

        $budget->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Orçamento removido.',
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function budgetPayload(Budget $budget): array
    {
        return [
            'id' => $budget->id,
            'category' => $budget->category ? [
                'id' => $budget->category->id,
                'name' => $budget->category->name,
                'type' => $budget->category->type->value,
                'color' => $budget->category->color,
                'icon' => $budget->category->icon,
            ] : null,
            'category_id' => $budget->category_id,
            'month' => $budget->month,
            'limit' => $budget->limit_cents,
            'limit_cents' => $budget->limit_cents,
            'spent' => $budget->spentCents(),
            'spent_cents' => $budget->spentCents(),
            'remaining' => $budget->remainingCents(),
            'remaining_cents' => $budget->remainingCents(),
            'percentage' => $budget->percentage(),
            'progress' => $budget->percentage(),
            'is_over' => $budget->isOverBudget(),
            'over_budget' => $budget->isOverBudget(),
            'status' => $budget->isOverBudget() ? 'over' : ($budget->percentage() >= 80 ? 'warning' : 'ok'),
        ];
    }
}
