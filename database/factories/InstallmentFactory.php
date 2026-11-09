<?php

namespace Database\Factories;

use App\Enums\InstallmentStatus;
use App\Models\CardPurchase;
use App\Models\CreditCard;
use App\Models\Installment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installment>
 */
class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'credit_card_id' => fn (array $attributes) => CreditCard::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'card_purchase_id' => fn (array $attributes) => CardPurchase::factory()->create([
                'user_id' => $attributes['user_id'],
                'credit_card_id' => $attributes['credit_card_id'],
            ])->id,
            'invoice_id' => null,
            'number' => 1,
            'amount' => 1000,
            'due_date' => now()->toDateString(),
            'status' => InstallmentStatus::Pending,
            'paid_at' => null,
        ];
    }
}
