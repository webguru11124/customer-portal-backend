<?php

namespace Tests\Unit\Actions\MagicLink;

use App\Actions\MagicLink\MagicJWTGeneratorAction;
use App\MagicLink\MagicLinkJWT;
use App\Models\User;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class MagicJWTGeneratorActionTest extends TestCase
{
    use RandomIntTestData;

    private const EMAIL = 'magic@link.com';
    private const HOURS = 2;
    private const TOKEN = 'eyJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhY21lLmNvbSIsImlhdCI6MTcxNDQwOTM4OSwiZXhwIjoxNzE0NDEyOTg5LCJuYmYiOjE3MTQ0MDkzODksImp0aSI6IjZXUmFVQ1hLSjZiTU1RT00iLCJzdWIiOiJlbXB0eSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjciLCJlbWFpbCI6InRlc3Rvdnlha2twbGVhc2VpZ25vcmVAZ21haWwuY29tIn0.Ar25NTei9xD-yCqbHcIcANlVB5MMck_uvppXg8frsg0';

    protected MagicJWTGeneratorAction $action;

    protected MockInterface|MagicLinkJWT $magicLinkJwtMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->magicLinkJwtMock = Mockery::mock(MagicLinkJWT::class);

        $this->action = new MagicJWTGeneratorAction($this->magicLinkJwtMock);
    }

    public function test_it_returns_token_for_existing_pest_routes_user(): void
    {
        $user = User::factory()->make([
            'email' => 'test@test.com',
        ]);

        $this->magicLinkJwtMock->shouldReceive('fromUser')
            ->with($user)
            ->andReturn(self::TOKEN);

        $result = ($this->action)($user);

        self::assertEquals(self::TOKEN, $result);
    }
}
