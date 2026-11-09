<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_admin_user(): void
    {
        $this->artisan('meusaldo:create-admin', [
            '--name' => 'Administrador Meu Saldo',
            '--email' => 'Admin@MeuSaldo.com',
            '--password' => 'SenhaForte!2026',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'admin@meusaldo.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Administrador Meu Saldo', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('SenhaForte!2026', $user->password));
    }

    public function test_it_updates_an_existing_admin_without_creating_duplicates(): void
    {
        $this->artisan('meusaldo:create-admin', [
            '--email' => 'admin@meusaldo.com',
            '--password' => 'SenhaForte!2026',
        ])->assertSuccessful();

        $this->artisan('meusaldo:create-admin', [
            '--name' => 'Novo Nome',
            '--email' => 'admin@meusaldo.com',
            '--password' => 'OutraSenha!2026',
        ])->assertSuccessful();

        $this->assertSame(1, User::query()->where('email', 'admin@meusaldo.com')->count());
        $this->assertSame('Novo Nome', User::query()->where('email', 'admin@meusaldo.com')->value('name'));
    }

    public function test_it_rejects_a_weak_password(): void
    {
        $this->artisan('meusaldo:create-admin', [
            '--email' => 'admin@meusaldo.com',
            '--password' => '123456',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@meusaldo.com',
        ]);
    }
}
