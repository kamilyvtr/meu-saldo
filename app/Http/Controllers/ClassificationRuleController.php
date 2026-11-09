<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassificationRuleRequest;
use App\Models\ClassificationRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassificationRuleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClassificationRule::class);

        $user = $request->user();

        return Inertia::render('classification-rules/Index', [
            'rules' => $user->classificationRules()
                ->with('category:id,name,type,color,icon')
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get()
                ->map(fn (ClassificationRule $rule): array => [
                    'id' => $rule->id,
                    'field' => $rule->field,
                    'operator' => $rule->operator,
                    'pattern' => $rule->pattern,
                    'priority' => $rule->priority,
                    'category' => $rule->category ? [
                        'id' => $rule->category->id,
                        'name' => $rule->category->name,
                        'type' => $rule->category->type->value,
                        'color' => $rule->category->color,
                        'icon' => $rule->category->icon,
                    ] : null,
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
        ]);
    }

    public function store(ClassificationRuleRequest $request): RedirectResponse
    {
        $this->authorize('create', ClassificationRule::class);

        $data = $request->validated();

        $request->user()->classificationRules()->create([
            'field' => $data['field'],
            'operator' => $data['operator'],
            'pattern' => $data['pattern'],
            'category_id' => $data['category_id'],
            'priority' => $data['priority'] ?? 0,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Regra criada com sucesso.',
        ]);

        return back();
    }

    public function update(ClassificationRuleRequest $request, ClassificationRule $rule): RedirectResponse
    {
        $this->authorize('update', $rule);

        $data = $request->validated();

        $rule->update([
            'field' => $data['field'],
            'operator' => $data['operator'],
            'pattern' => $data['pattern'],
            'category_id' => $data['category_id'],
            'priority' => $data['priority'] ?? 0,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Regra atualizada com sucesso.',
        ]);

        return back();
    }

    public function destroy(ClassificationRule $rule): RedirectResponse
    {
        $this->authorize('delete', $rule);

        $rule->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Regra removida.',
        ]);

        return back();
    }
}
