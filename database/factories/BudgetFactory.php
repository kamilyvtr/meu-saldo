<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->state(['type' => TransactionType::Expense]),
            'month' => now()->format('Y-m'),
            'limit_cents' => fake()->numberBetween(10_000, 500_000),
        ];
    }
}
