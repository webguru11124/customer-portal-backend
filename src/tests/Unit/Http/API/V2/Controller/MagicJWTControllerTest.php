<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\MagicLink\MagicJWTGeneratorAction;
use App\DTO\MagicLink\ValidationErrorDTO;
use App\MagicLink\Guards\MagicJwtAuthGuard;
use App\MagicLink\Providers\MagicLinkAuthEloquentUserProvider;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\HasHttpResponses;

final class MagicJWTControllerTest extends TestCase
{
    use HasHttpResponses;

    protected Guard|Mockery\MockInterface $magicLinkGuardMock;
    protected const EMAIL = 'magic@link.com';
    protected const TOKEN = 'eyJlIjoidGVzdG92eWFra3BsZWFzZWlnbm9yZUBnbWFpbC5jb20iLCJ4IjoxNzE0MTIzNjU5LCJzIjoiZWQ4NzMwNmUxYTMyZGY4MDJkY2RiNThhOGIxOWM0MzkifQ';
    private const JWT_TOKEN = 'eyJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhY21lLmNvbSIsImlhdCI6MTcxNDQwOTM4OSwiZXhwIjoxNzE0NDEyOTg5LCJuYmYiOjE3MTQ0MDkzODksImp0aSI6IjZXUmFVQ1hLSjZiTU1RT00iLCJzdWIiOiJlbXB0eSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjciLCJlbWFpbCI6InRlc3Rvdnlha2twbGVhc2VpZ25vcmVAZ21haWwuY29tIn0.Ar25NTei9xD-yCqbHcIcANlVB5MMck_uvppXg8frsg0';

    public function test_it_returns_unauthorized_when_accessing_magicjwt_without_a_key(): void
    {
        $this->postJson($this->getAdminMagicTokenRoute(), ['token' => self::TOKEN])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_unauthorized_when_accessing_magicjwt_with_an_incorrect_key(): void
    {
        $this->withHeader('Authorization', 'Bearer 11111111')
            ->withHeader('X-Auth-Type', MagicJwtAuthGuard::TYPE)
            ->postJson($this->getAdminMagicTokenRoute(), ['token' => self::EMAIL])
            ->assertStatus(ValidationErrorDTO::INVALID_TOKEN_CODE);
    }

    public function test_it_returns_link(): void
    {
        $user = User::factory()->make([
            'email' => self::EMAIL,
            'email_verified' => true,
        ]);

        $magicLinkGuardMock = Mockery::mock(Guard::class);
        $magicLinkGuardMock->expects('user')
            ->twice()
            ->andReturn($user);
        $authFactoryMock = Mockery::mock(AuthFactory::class)
            ->makePartial();
        $providerMock = Mockery::mock(MagicLinkAuthEloquentUserProvider::class);
        $authFactoryMock->expects('createUserProvider')
            ->once()
            ->andReturn($providerMock);
        $authFactoryMock->expects('guard')
            ->with('magiclinkguard')
            ->twice()
            ->andReturn($magicLinkGuardMock);
        $this->instance(AuthFactory::class, $authFactoryMock);


        $action = Mockery::mock(MagicJWTGeneratorAction::class);
        $action->shouldReceive('__invoke')
            ->with($user)
            ->andReturn(self::JWT_TOKEN);
        $this->instance(MagicJWTGeneratorAction::class, $action);

        $response = $this->withHeader('X-Auth-Type', MagicJwtAuthGuard::TYPE)
            ->withHeader('Authorization', 'Bearer ' . self::TOKEN)
            ->postJson($this->getAdminMagicTokenRoute(), ['token' => self::TOKEN]);

        $this->assertEquals(self::JWT_TOKEN, $response['jwt']);
    }

    private function getAdminMagicTokenRoute(array $params = []): string
    {
        return route('api.v2.get-jwt-token', $params);
    }
}
