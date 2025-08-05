<?php

declare(strict_types=1);

namespace App\Services\Auth0;

use App\Exceptions\Auth0\ApiRequestFailedException;
use App\Interfaces\Auth0\UserService as Auth0UserRepository;
use App\Services\LogService;
use Aptive\Component\Http\HttpStatus;
use Auth0\SDK\Contract\Auth0Interface;
use Auth0\SDK\Exception\Auth0Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UserService implements Auth0UserRepository
{
    public function __construct(
        private readonly Auth0Interface $auth0
    ) {
    }

    /**
     * @inheritdoc
     */
    public function isRegisteredEmail(string $email): bool
    {
        try {
            $response = $this
                ->auth0
                ->management()
                ->usersByEmail()
                ->get($email);
        } catch (Auth0Exception $e) {
            throw new ApiRequestFailedException($e->getMessage(), previous: $e);
        }

        $responseJson = $response->getBody()->getContents();

        Log::debug(LogService::AUTH0_USERS_BY_EMAIL_RESPONSE, [
            'email' => $email,
            'status' => $response->getStatusCode(),
            'response' => $responseJson,
        ]);

        if ($response->getStatusCode() !== HttpStatus::OK) {
            throw new ApiRequestFailedException('Users by email request failed');
        }

        $users = $this->decodeResponse($responseJson);

        return count($users) > 0;
    }

    /**
     * @param string $auth0UserId
     *
     * @return void
     */
    public function resendVerificationEmail(string $auth0UserId): void
    {
        try {
            $response = $this
                ->auth0
                ->management()
                ->jobs()
                ->createSendVerificationEmail($auth0UserId);
        } catch (Auth0Exception $e) {
            throw new ApiRequestFailedException($e->getMessage(), previous: $e);
        }

        if ($response->getStatusCode() !== HttpStatus::CREATED) {
            throw new ApiRequestFailedException('Send verification email request failed');
        }
    }

    /**
     * @param string $responseJson
     *
     * @return array<string, array<string, mixed>>
     */
    private function decodeResponse(string $responseJson): array
    {
        try {
            return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ApiRequestFailedException('Invalid JSON received from Auth0 API', previous: $e);
        }
    }
}
