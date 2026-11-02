<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\User\CreateUser;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * The only way to create real users (and admins) on staging/production:
 * seeders never run there (ngp-laravel §6). Rename the "app:" prefix to the
 * product name when you start a product.
 */
final class CreateUserCommand extends Command
{
    protected $signature = 'app:create-user
        {--name= : Full name}
        {--email= : Email address used to sign in}
        {--admin : Give the user the admin role}';

    protected $description = 'Create a user; asks for the password so it never lands in shell history';

    public function handle(CreateUser $createUser): int
    {
        // Console output is for developers and operators: keep it in English.
        $this->laravel->setLocale('en');

        $input = [
            'name' => $this->stringOption('name') ?? text(label: 'Name', required: true),
            'email' => Str::lower(trim($this->stringOption('email') ?? text(label: 'Email', required: true))),
            'password' => password(label: 'Password', required: true, hint: 'At least 8 characters.'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $role = $this->option('admin') === true ? UserRole::Admin : UserRole::User;
        $user = $createUser->handle($input['name'], $input['email'], $input['password'], $role);

        $this->components->info("Created {$role->value} {$user->email} ({$user->id}).");

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
