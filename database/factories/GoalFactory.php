<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'target_cents' => fake()->numberBetween(10_000, 500_000),
            'current_cents' => 0,
            'deadline' => now()->addMonths(3)->toDateString(),
            'account_id' => null,
        ];
    }
}
