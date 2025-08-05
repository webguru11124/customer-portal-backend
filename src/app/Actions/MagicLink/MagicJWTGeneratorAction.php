<?php

declare(strict_types=1);

namespace App\Actions\MagicLink;

use App\Models\User;
use App\MagicLink\MagicLinkJWT;

class MagicJWTGeneratorAction
{
    public function __construct(
        private readonly MagicLinkJWT $jwt,
    ) {
    }

    /**
     * @param User $user
     *
     * @return string
     */
    public function __invoke(User $user): string
    {
        return $this->jwt->fromUser($user);
    }
}
