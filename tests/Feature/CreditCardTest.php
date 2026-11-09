<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\CreditCard;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_creates_expense_and_increases_card_debt(): void
    {
        $user = User::factory()->create();
        $card = CreditCard::factory()->create(['user_id' => $user->id]);
        $countBefore = Transaction::count();

        $this->actingAs($user)
            ->post(route('card-purchases.store'), [
                'credit_card_id' => $card->id,
                'description' => 'Compra teste',
                'total_amount_cents' => 10000,
                'purchase_date' => now()->toDateString(),
                'installments_count' => 2,
            ])
            ->assertRedirect();

        $this->assertSame($countBefore + 1, Transaction::count());

        $expense = Transaction::where('type', 'expense')->latest('id')->first();
        $this->assertNotNull($expense);
        $this->assertSame(10000, $expense->amount);

        $card->refresh();
        $this->assertSame(10000, $card->debt_cents);
        $this->assertSame(2, $card->installments()->count());
        $this->assertSame(2, $card->invoices()->count());
    }

    public function test_paying_invoice_debits_bank_and_reduces_debt_without_new_expense(): void
    {
        $user = User::factory()->create();
        $card = CreditCard::factory()->create(['user_id' => $user->id]);
        $bank = Account::factory()->create([
            'user_id' => $user->id,
            'type' => AccountType::Checking,
            'balance' => 100000,
        ]);

        $this->actingAs($user)
            ->post(route('card-purchases.store'), [
                'credit_card_id' => $card->id,
                'description' => 'Compra teste',
                'total_amount_cents' => 10000,
                'purchase_date' => now()->toDateString(),
                'installments_count' => 1,
            ])
            ->assertRedirect();

        $invoice = Invoice::where('credit_card_id', $card->id)->firstOrFail();
        $txCountBefore = Transaction::count();

        $this->actingAs($user)
            ->post(route('invoices.pay', $invoice), [
                'account_id' => $bank->id,
                'paid_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $bank->refresh();
        $card->refresh();
        $invoice->refresh();

        $this->assertSame(90000, $bank->balance);
        $this->assertSame(0, $card->debt_cents);
        $this->assertSame('paid', $invoice->status->value);
        $this->assertSame($txCountBefore, Transaction::count());
    }

    public function test_invoice_groups_installments(): void
    {
        $user = User::factory()->create();
        $card = CreditCard::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('card-purchases.store'), [
                'credit_card_id' => $card->id,
                'description' => 'Compra parcelada',
                'total_amount_cents' => 9999,
                'purchase_date' => now()->toDateString(),
                'installments_count' => 3,
            ])
            ->assertRedirect();

        $invoice = Invoice::where('credit_card_id', $card->id)->firstOrFail();
        $this->assertSame(9999, (int) $card->invoices()->sum('total_amount'));
        $this->assertSame(3, $card->installments()->count());
    }
}
