<?php

declare(strict_types=1);

namespace App\MagicLink\Guards;

use App\DTO\MagicLink\ValidationErrorDTO;
use App\Models\User;
use App\MagicLink\MagicLink;
use App\MagicLink\Providers\MagicLinkAuthEloquentUserProvider;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;

class MagicLinkGuard implements Guard
{
    use GuardHelpers, Macroable {
        __call as macroCall;
    }
    public const TYPE = 'MagicLink';

    protected MagicLink $mlp;

    /** @var MagicLinkAuthEloquentUserProvider */
    protected $provider;

    /** @var User|null */
    protected $user;

    /** @var \Illuminate\Http\Request */
    protected $request;

    public function __construct(
        MagicLink $mlp,
        MagicLinkAuthEloquentUserProvider $provider,
        Request $request
    ) {
        $this->mlp = $mlp;
        $this->provider = $provider;
        $this->request = $request;
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

        $payload = $this->mlp->decode((string) $this->request->bearerToken());
        if (!empty($payload)) {
            $this->user = $this->provider->findUserByEmail($payload['e']);
            if (empty($this->user)) {
                $this->user = $this->provider->getModelFromMagicLinkPayload($payload);
            }
        }

        return $this->user;
    }

    public function getValidationError(): ValidationErrorDTO|null
    {
        return $this->mlp->getValidationError();
    }

    /**
     * @param array<string, mixed> $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        return true;
    }
}
