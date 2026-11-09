<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes) => Account::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'type' => fake()->randomElement(TransactionType::cases()),
            'category_id' => fn (array $attributes) => Category::factory()->create([
                'user_id' => $attributes['user_id'],
                'type' => $attributes['type'],
            ])->id,
            'description' => fake()->sentence(3),
            'amount' => fake()->numberBetween(100, 100_000),
            'date' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
            'notes' => null,
        ];
    }

    /**
     * Indicate that the transaction is an income.
     */
    public function income(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Income,
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Expense,
        ]);
    }
}
