<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * E-mail do usuário administrador criado pelo seeder.
     */
    public const EMAIL = 'admin@meusaldo.com';

    /**
     * Senha inicial do usuário administrador criado pelo seeder.
     */
    public const PASSWORD = 'Admin@123456';

    /**
     * Seed the application's administrator user.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Administrador',
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info('Usuário administrador disponível: '.self::EMAIL);
    }
}
