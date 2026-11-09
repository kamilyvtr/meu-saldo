<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Recurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurrenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_update_toggle_and_delete_a_recurrence(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $this->actingAs($user);

        $data = [
            'description' => 'Internet',
            'amount_cents' => 12990,
            'type' => 'expense',
            'account_id' => $account->id,
            'frequency' => 'monthly',
            'next_run_date' => now()->toDateString(),
        ];

        $this->post('/recurrences', $data)->assertRedirect();
        $recurrence = Recurrence::query()->where('user_id', $user->id)->firstOrFail();

        $this->put("/recurrences/{$recurrence->id}", [...$data, 'description' => 'Internet atualizado'])
            ->assertRedirect();
        $this->assertDatabaseHas('recurrences', ['id' => $recurrence->id, 'description' => 'Internet atualizado']);

        $this->patch("/recurrences/{$recurrence->id}/toggle")->assertRedirect();
        $this->assertDatabaseHas('recurrences', ['id' => $recurrence->id, 'is_active' => false]);

        $this->delete("/recurrences/{$recurrence->id}")->assertRedirect();
        $this->assertDatabaseMissing('recurrences', ['id' => $recurrence->id]);
    }

    public function test_user_cannot_use_another_users_account_or_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($otherUser)->create();
        $category = Category::factory()->for($otherUser)->create();

        $this->actingAs($user)->post('/recurrences', [
            'description' => 'Internet',
            'amount_cents' => 12990,
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'frequency' => 'monthly',
            'next_run_date' => now()->toDateString(),
        ])->assertSessionHasErrors(['account_id', 'category_id']);

        $this->assertDatabaseCount('recurrences', 0);
    }

    public function test_user_cannot_change_another_users_recurrence(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($owner)->create();
        $recurrence = Recurrence::factory()->for($owner)->create(['account_id' => $account->id]);

        $this->actingAs($otherUser)
            ->patch("/recurrences/{$recurrence->id}/toggle")
            ->assertForbidden();

        $this->assertDatabaseHas('recurrences', ['id' => $recurrence->id, 'is_active' => true]);
    }
}
