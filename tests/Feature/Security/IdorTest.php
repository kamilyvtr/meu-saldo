<?php

namespace Tests\Feature\Security;

use App\Models\CreditCard;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_b_cannot_view_user_a_credit_card(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $card = CreditCard::factory()->create(['user_id' => $userA->id]);

        $this->actingAs($userB)
            ->get(route('credit-cards.show', $card))
            ->assertForbidden();
    }

    public function test_user_b_cannot_view_user_a_invoice_in_index(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Invoice::factory()->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB)->get(route('invoices.index'));

        $response->assertOk();
        $response->assertSee('"invoices":[]', false);
    }

    public function test_user_b_cannot_pay_user_a_invoice(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $userA->id]);

        $this->actingAs($userB)
            ->post(route('invoices.pay', $invoice), [
                'account_id' => 1,
                'paid_date' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_user_b_cannot_close_user_a_invoice(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $userA->id]);

        $this->actingAs($userB)
            ->post(route('invoices.close', $invoice))
            ->assertForbidden();
    }
}
