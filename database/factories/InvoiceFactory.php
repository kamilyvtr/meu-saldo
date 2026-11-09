<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\CreditCard;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'credit_card_id' => fn (array $attributes) => CreditCard::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'month' => now()->format('Y-m'),
            'closing_date' => null,
            'due_date' => null,
            'status' => InvoiceStatus::Open,
            'total_amount' => 0,
            'paid_at' => null,
            'paid_from_account_id' => null,
        ];
    }
}
