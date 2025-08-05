<?php

namespace App\Interfaces\Repository;

use App\Models\User;

interface UserRepository
{
    public function userExists(string $email): bool;
    public function getUser(string $email): User|null;
    public function deleteUserWithAccounts(string $email): bool;
}
