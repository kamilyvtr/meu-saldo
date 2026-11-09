<?php

namespace Database\Factories;

use App\Models\CardPurchase;
use App\Models\CreditCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardPurchase>
 */
class CardPurchaseFactory extends Factory
{
    protected $model = CardPurchase::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'credit_card_id' => fn (array $attributes) => CreditCard::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'category_id' => null,
            'description' => fake()->sentence(3),
            'total_amount' => fake()->numberBetween(1000, 100000),
            'purchase_date' => fake()->date(),
            'installments_count' => 1,
            'notes' => null,
        ];
    }
}
