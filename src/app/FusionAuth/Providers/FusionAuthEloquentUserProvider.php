<?php

declare(strict_types=1);

namespace App\FusionAuth\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Tymon\JWTAuth\Payload;

class FusionAuthEloquentUserProvider extends EloquentUserProvider
{
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
            ->setAttribute('fusionauth_id', $payload->get('sub'))
            ->setAttribute('email', $email)
            ->setAttribute('first_name', '')
            ->setAttribute('last_name', '');
        return $model;
    }

    /**
     * @param string $identifier
     * @return User|null
     */
    public function findUserByFusionAuthId(string $identifier): User|null
    {
        $model = $this->createModel();
        /** @var \App\Models\User|null $model */
        $model = $this->newModelQuery($model)
            ->where('fusionauth_id', $identifier)
            ->first();
        return $model;
    }
}
