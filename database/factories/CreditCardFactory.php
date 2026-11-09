<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\CreditCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCard>
 */
class CreditCardFactory extends Factory
{
    protected $model = CreditCard::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes) => Account::factory()->create([
                'user_id' => $attributes['user_id'],
                'type' => AccountType::CreditCard,
                'balance' => 0,
            ])->id,
            'name' => fake()->company().' Card',
            'brand' => fake()->randomElement(['Visa', 'Mastercard', 'Elo']),
            'limit_cents' => 500000,
            'closing_day' => 1,
            'due_day' => 10,
            'color' => fake()->hexColor(),
            'institution' => fake()->company(),
            'is_active' => true,
        ];
    }
}
