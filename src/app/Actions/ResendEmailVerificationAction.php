<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\Auth0\ApiRequestFailedException;
use App\Interfaces\Auth0\UserService as Auth0UserService;

class ResendEmailVerificationAction
{
    public function __construct(
        private readonly Auth0UserService $auth0UserService
    ) {
    }

    /**
     * @param string $userId
     * @return void
     * @throws ApiRequestFailedException when request fails for whatever reason
     */
    public function __invoke(string $userId): void
    {
        $this->auth0UserService->resendVerificationEmail($userId);
    }
}
