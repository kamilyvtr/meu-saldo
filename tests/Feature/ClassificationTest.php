<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ClassificationRule;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_can_be_created(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        $this->actingAs($user)
            ->post(route('classification-rules.store'), [
                'field' => 'description',
                'operator' => 'contains',
                'pattern' => 'Uber',
                'category_id' => $category->id,
                'priority' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('classification_rules', [
            'user_id' => $user->id,
            'pattern' => 'Uber',
            'category_id' => $category->id,
        ]);
    }

    public function test_rule_suggests_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        ClassificationRule::create([
            'user_id' => $user->id,
            'field' => 'description',
            'operator' => 'contains',
            'pattern' => 'Uber',
            'category_id' => $category->id,
            'priority' => 10,
        ]);

        $service = app(ClassificationService::class);
        $suggested = $service->suggest($user, 'Uber trip', 'expense');

        $this->assertNotNull($suggested);
        $this->assertSame($category->id, $suggested->id);
    }

    public function test_frequency_suggests_after_three(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'description' => 'Supermercado Extra 123',
                'type' => 'expense',
            ]);
        }

        $service = app(ClassificationService::class);
        $suggested = $service->suggest($user, 'Supermercado Extra 456', 'expense');

        $this->assertNotNull($suggested);
        $this->assertSame($category->id, $suggested->id);
    }

    public function test_inbox_categorize(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => null,
            'type' => 'expense',
        ]);

        $this->actingAs($user)
            ->patch(route('transactions.categorize', $transaction), [
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'category_id' => $category->id,
        ]);
    }
}
