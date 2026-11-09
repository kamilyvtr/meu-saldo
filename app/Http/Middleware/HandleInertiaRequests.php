<?php

namespace App\Http\Middleware;

use App\Models\Account;
use App\Models\Category;
use App\Support\DefaultCategories;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'quickAccounts' => function () use ($request): array {
                $user = $request->user();

                if ($user === null) {
                    return [];
                }

                return $user->accounts()
                    ->orderByDesc('is_active')
                    ->orderBy('name')
                    ->get(['id', 'name', 'type', 'balance', 'color', 'is_active'])
                    ->map(fn (Account $account): array => [
                        'id' => $account->id,
                        'name' => $account->name,
                        'type' => $account->type->value,
                        'balance' => $account->balance,
                        'color' => $account->color,
                        'is_active' => $account->is_active,
                    ])
                    ->values()
                    ->all();
            },
            'quickCategories' => function () use ($request): array {
                $user = $request->user();

                if ($user === null) {
                    return [];
                }

                DefaultCategories::createFor($user);

                return $user->categories()
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
            },
        ];
    }
}
