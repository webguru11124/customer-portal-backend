<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller\Admin;

use App\Actions\MagicLink\MagicLinkGeneratorAction;
use App\Exceptions\Account\AccountNotFoundException;
use Illuminate\Support\Facades\Config;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\HasHttpResponses;

final class MagicLinkControllerTest extends TestCase
{
    use HasHttpResponses;

    private const TEST_API_KEY = '1234567';
    private const EMAIL = 'magic@link.com';
    private const TOKEN = 'eyJlIjoidGVzdG92eWFra3BsZWFzZWlnbm9yZUBnbWFpbC5jb20iLCJ4IjoxNzE0MTIzNjU5LCJzIjoiZWQ4NzMwNmUxYTMyZGY4MDJkY2RiNThhOGIxOWM0MzkifQ';

    public function test_it_returns_unauthorized_when_accessing_magiclink_without_a_key(): void
    {
        $this->postJson($this->getAdminMagicLinkRoute(), ['email' => self::EMAIL])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_unauthorized_when_accessing_magiclink_with_an_incorrect_key(): void
    {
        $this->setApiKeyConfig();

        $this->withHeader('Authorization', 'Bearer 11111111')
            ->postJson($this->getAdminMagicLinkRoute(), ['email' => self::EMAIL])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_link(): void
    {
        $this->setApiKeyConfig();

        $action = Mockery::mock(MagicLinkGeneratorAction::class);
        $action->shouldReceive('__invoke')
            ->andReturn(self::TOKEN);
        $this->instance(MagicLinkGeneratorAction::class, $action);

        $response = $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->postJson($this->getAdminMagicLinkRoute(), ['email' => self::EMAIL]);

        $this->assertEquals(self::TOKEN, $response['link']);
    }

    public function test_it_returns_404_on_account_not_found_exception(): void
    {
        $this->setApiKeyConfig();

        $action = Mockery::mock(MagicLinkGeneratorAction::class);
        $action->shouldReceive('__invoke')
            ->andThrow(new AccountNotFoundException());
        $this->instance(MagicLinkGeneratorAction::class, $action);

        $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->postJson($this->getAdminMagicLinkRoute(), ['email' => self::EMAIL])
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider provideInvalidRequestData
     */
    public function test_it_returns_validation_exception_on_invalid_email(
        array $request,
        string $errorPath,
        string $errorMessage,
    ): void {
        $this->setApiKeyConfig();

        $action = Mockery::mock(MagicLinkGeneratorAction::class);
        $action->shouldReceive('__invoke')
            ->andThrow(new AccountNotFoundException());
        $this->instance(MagicLinkGeneratorAction::class, $action);

        $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->postJson($this->getAdminMagicLinkRoute(), $request)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath($errorPath, $errorMessage);
    }

    public function provideInvalidRequestData(): array
    {
        return [
            'invalidEmail' => [
                'request' => ['email' => 'Let me in!'],
                'errorPath' => 'errors.email.0',
                'errorMessage' => 'The email must be a valid email address.'
            ],
            'negative hours' => [
                'request' => ['email' => self::EMAIL, 'hours' => -1],
                'errorPath' => 'errors.hours.0',
                'errorMessage' => 'The hours must be greater than or equal to 0.'
            ],
            'string hours' => [
                'request' => ['email' => self::EMAIL, 'hours' => 'one'],
                'errorPath' => 'errors.hours.0',
                'errorMessage' => 'The hours must be a number.'
            ],
        ];
    }

    private function setApiKeyConfig($permission = '*')
    {
        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => ['permissions' => $permission]]);
    }

    private function getAdminMagicLinkRoute(array $params = []): string
    {
        return route('api.v2.admin.magiclink', $params);
    }
}
