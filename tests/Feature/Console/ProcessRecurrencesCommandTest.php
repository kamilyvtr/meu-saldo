<?php

namespace Tests\Feature\Console;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Recurrence;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessRecurrencesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_transaction_and_advances_next_run_date(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['balance' => 1000]);

        $recurrence = Recurrence::factory()->for($user)->create([
            'description' => 'Aluguel',
            'amount_cents' => 5000,
            'type' => TransactionType::Expense->value,
            'account_id' => $account->id,
            'frequency' => 'monthly',
            'next_run_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->artisan('recurrences:process')->assertSuccessful();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'description' => 'Aluguel',
            'type' => 'expense',
            'amount' => 5000,
            'date' => now()->toDateString().' 00:00:00',
        ]);

        $recurrence->refresh();
        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $recurrence->next_run_date->toDateString());

        $account->refresh();
        $this->assertSame(1000 - 5000, $account->balance);
    }

    public function test_it_does_not_duplicate_on_second_run(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['balance' => 0]);

        Recurrence::factory()->for($user)->create([
            'description' => 'Salário',
            'amount_cents' => 10000,
            'type' => TransactionType::Income->value,
            'account_id' => $account->id,
            'frequency' => 'weekly',
            'next_run_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->artisan('recurrences:process')->assertSuccessful();
        $this->artisan('recurrences:process')->assertSuccessful();

        $this->assertSame(1, Transaction::query()->where('user_id', $user->id)->count());
    }

    public function test_it_skips_inactive_recurrences(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['balance' => 0]);

        Recurrence::factory()->for($user)->create([
            'description' => 'Pausada',
            'amount_cents' => 1000,
            'type' => TransactionType::Expense->value,
            'account_id' => $account->id,
            'frequency' => 'monthly',
            'next_run_date' => now()->toDateString(),
            'is_active' => false,
        ]);

        $this->artisan('recurrences:process')->assertSuccessful();

        $this->assertSame(0, Transaction::query()->where('user_id', $user->id)->count());
    }

    public function test_it_deactivates_a_recurrence_after_its_end_date(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $recurrence = Recurrence::factory()->for($user)->create([
            'account_id' => $account->id,
            'next_run_date' => now()->subDay()->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
            'is_active' => true,
        ]);

        $this->artisan('recurrences:process')->assertSuccessful();

        $this->assertDatabaseHas('recurrences', ['id' => $recurrence->id, 'is_active' => false]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_it_processes_the_end_date_then_deactivates_the_recurrence(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $recurrence = Recurrence::factory()->for($user)->create([
            'account_id' => $account->id,
            'next_run_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->artisan('recurrences:process')->assertSuccessful();

        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'account_id' => $account->id]);
        $this->assertDatabaseHas('recurrences', ['id' => $recurrence->id, 'is_active' => false]);
    }
}
