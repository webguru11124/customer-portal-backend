<?php

namespace App\Interfaces\Auth0;

use App\Exceptions\Auth0\ApiRequestFailedException;

interface UserService
{
    /**
     * @param string $email
     *
     * @return bool
     *
     * @throws ApiRequestFailedException when request fails for whatever reason
     */
    public function isRegisteredEmail(string $email): bool;

    /**
     * @param string $auth0UserId
     *
     * @return void
     *
     * @throws ApiRequestFailedException when request fails for whatever reason
     */
    public function resendVerificationEmail(string $auth0UserId): void;
}
