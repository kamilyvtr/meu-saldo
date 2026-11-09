<?php

namespace Database\Factories;

use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Models\Recurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recurrence>
 */
class RecurrenceFactory extends Factory
{
    protected $model = Recurrence::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => fake()->words(3, true),
            'amount_cents' => fake()->numberBetween(1000, 50000),
            'type' => TransactionType::Expense->value,
            'category_id' => null,
            'account_id' => null,
            'frequency' => RecurrenceFrequency::Monthly->value,
            'next_run_date' => now()->toDateString(),
            'end_date' => null,
            'is_active' => true,
        ];
    }
}