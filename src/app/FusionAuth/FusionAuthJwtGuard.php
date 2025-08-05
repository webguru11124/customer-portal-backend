<?php

declare(strict_types=1);

namespace App\FusionAuth;

use App\Models\User;
use App\FusionAuth\Providers\FusionAuthEloquentUserProvider;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard as TymonJWTGuard;
use Tymon\JWTAuth\Payload;

class FusionAuthJwtGuard extends TymonJWTGuard
{
    public const TYPE = 'fusion';
    /**
     * @var FusionAuthEloquentUserProvider
     */
    protected $provider;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param JWT $jwt
     * @param FusionAuthEloquentUserProvider $provider
     * @param Request $request
     */
    public function __construct(JWT $jwt, FusionAuthEloquentUserProvider $provider, Request $request)
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
            $user = $this->provider->findUserByFusionAuthId($payload['sub']);
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
            $this->user = $this->provider->getModelFromPayload($payload);
            return $this->user;
        } catch (JWTException $e) {
            return null;
        }
    }
}
