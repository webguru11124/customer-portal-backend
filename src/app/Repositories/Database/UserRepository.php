<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Interfaces\Repository\UserRepository as UserRepositoryInterface;
use App\Models\User;

final class UserRepository implements UserRepositoryInterface
{
    public function userExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function getUser(string $email): User|null
    {
        return User::where('email', $email)->first();
    }

    public function deleteUserWithAccounts(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            return false;
        }

        return (bool) $user->delete();
    }
}
