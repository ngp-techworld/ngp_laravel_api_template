<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;
use SensitiveParameter;

final class CreateUser
{
    /**
     * Input is validated by the caller (FormRequest or console command).
     */
    public function handle(
        string $name,
        string $email,
        #[SensitiveParameter] string $password,
        UserRole $role = UserRole::User,
    ): User {
        $user = new User([
            'name' => $name,
            'email' => Str::lower($email),
            'password' => $password, // hashed by the model cast
        ]);

        $user->role = $role;
        $user->save();

        return $user;
    }
}
