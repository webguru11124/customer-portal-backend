<?php

declare(strict_types=1);

namespace App\MagicLink\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Tymon\JWTAuth\Payload;

class MagicLinkAuthEloquentUserProvider extends EloquentUserProvider
{
    /**
     * @param string $email
     * @return User|null
     */
    public function findUserByEmail(string $email): User|null
    {
        $model = $this->createModel();
        /** @var \App\Models\User|null $model */
        $model = $this->newModelQuery($model)
            ->where('email', $email)
            ->first();
        return $model;
    }

    /**
     * @param array<string, mixed> $payload
     * @return User
     */
    public function getModelFromMagicLinkPayload(array $payload): User
    {
        $email = $payload['e'];

        /** @var \App\Models\User $model */
        $model = $this->createModel()
            ->setAttribute('email', $email)
            ->setAttribute('first_name', '')
            ->setAttribute('last_name', '');
        return $model;
    }

    /**
     * Returns a user from the provided payload
     *
     * @param \Tymon\JWTAuth\Payload $payload
     *
     * @return \App\Models\User
     */
    public function getModelFromPayload(Payload $payload): User
    {
        $email = $payload->get('email');

        /** @var \App\Models\User $model */
        $model = $this->createModel()
            ->setAttribute('email', $email)
            ->setAttribute('first_name', '')
            ->setAttribute('last_name', '');
        return $model;
    }
}
