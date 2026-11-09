<?php

namespace App\Support;

use App\Enums\TransactionType;
use App\Models\User;

final class DefaultCategories
{
    /**
     * Create the default categories for a user that does not have any yet.
     */
    public static function createFor(User $user): void
    {
        if ($user->categories()->exists()) {
            return;
        }

        $categories = [
            ['name' => 'Alimentação', 'type' => TransactionType::Expense, 'icon' => 'utensils', 'color' => '#f97316'],
            ['name' => 'Moradia', 'type' => TransactionType::Expense, 'icon' => 'home', 'color' => '#8b5cf6'],
            ['name' => 'Transporte', 'type' => TransactionType::Expense, 'icon' => 'car', 'color' => '#3b82f6'],
            ['name' => 'Saúde', 'type' => TransactionType::Expense, 'icon' => 'heart-pulse', 'color' => '#ef4444'],
            ['name' => 'Educação', 'type' => TransactionType::Expense, 'icon' => 'graduation-cap', 'color' => '#06b6d4'],
            ['name' => 'Lazer', 'type' => TransactionType::Expense, 'icon' => 'gamepad-2', 'color' => '#ec4899'],
            ['name' => 'Assinaturas', 'type' => TransactionType::Expense, 'icon' => 'clapperboard', 'color' => '#f47422'],
            ['name' => 'Compras', 'type' => TransactionType::Expense, 'icon' => 'shopping-bag', 'color' => '#eab308'],
            ['name' => 'Outros gastos', 'type' => TransactionType::Expense, 'icon' => 'circle-ellipsis', 'color' => '#71717a'],
            ['name' => 'Salário', 'type' => TransactionType::Income, 'icon' => 'banknote', 'color' => '#10b981'],
            ['name' => 'Freelance', 'type' => TransactionType::Income, 'icon' => 'laptop', 'color' => '#22c55e'],
            ['name' => 'Investimentos', 'type' => TransactionType::Income, 'icon' => 'trending-up', 'color' => '#14b8a6'],
            ['name' => 'Outras entradas', 'type' => TransactionType::Income, 'icon' => 'circle-plus', 'color' => '#84cc16'],
        ];

        $user->categories()->createMany($categories);
    }
}
