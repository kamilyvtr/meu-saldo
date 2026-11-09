<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateAdminUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meusaldo:create-admin
        {--name=Administrador : Nome exibido do administrador}
        {--email=admin@meusaldo.com : E-mail de acesso do administrador}
        {--password= : Senha forte. Se omitida, uma senha segura será gerada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria ou atualiza o usuário administrador inicial do Meu Saldo';

    public function handle(): int
    {
        $name = trim((string) $this->option('name'));
        $email = Str::lower(trim((string) $this->option('email')));
        $password = (string) ($this->option('password') ?: Str::password(20));

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => [
                    'required',
                    'string',
                    Password::min(12)->mixedCase()->numbers()->symbols(),
                ],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $wasRecentlyCreated = ! $user->exists;

        $user->name = $name;
        $user->password = $password;
        $user->email_verified_at = now();
        $user->save();

        $this->newLine();
        $this->components->info(
            $wasRecentlyCreated
                ? 'Usuário administrador criado com sucesso.'
                : 'Usuário administrador atualizado com sucesso.',
        );

        $this->components->twoColumnDetail('Nome', $user->name);
        $this->components->twoColumnDetail('E-mail', $user->email);
        $this->components->twoColumnDetail('Senha', $password);
        $this->newLine();
        $this->components->warn(
            'Guarde esta senha em local seguro. Ela não será exibida novamente.',
        );

        return self::SUCCESS;
    }
}
