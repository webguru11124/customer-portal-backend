<?php

declare(strict_types=1);

namespace App\MagicLink\Guards;

use App\Models\User;
use App\MagicLink\Providers\MagicLinkAuthEloquentUserProvider;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard as TymonJWTGuard;
use Tymon\JWTAuth\Payload;

class MagicJwtAuthGuard extends TymonJWTGuard
{
    public const TYPE = 'MagicLink';

    /**
     * @var MagicLinkAuthEloquentUserProvider
     */
    protected $provider;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param JWT $jwt
     * @param MagicLinkAuthEloquentUserProvider $provider
     * @param Request $request
     */
    public function __construct(JWT $jwt, MagicLinkAuthEloquentUserProvider $provider, Request $request)
    {
        $this->jwt = $jwt;
        $this->provider = $provider;
        $this->request = $request;
        parent::__construct($jwt, $provider, $request);
    }

    /**
     * @return User|null
     */
    public function user(): User|null
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->request->header('X-Auth-Type') !== self::TYPE) {
            return null;
        }

        if ($this->jwt->setRequest($this->request)->getToken() &&
            ($payload = $this->jwt->check(true)) && $payload instanceof Payload &&
            $this->validateSubject()
        ) {
            $user = $this->provider->findUserByEmail($payload['email']);
            if ($user !== null) {
                $this->user = $user;
                return $user;
            }
        }

        try {
            $payload = $this->jwt->getPayload();
            if (!($payload instanceof Payload)) {
                return null;
            }
            return $this->provider->getModelFromPayload($payload);
        } catch (JWTException $e) {
            return null;
        }
    }
}
