<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Interfaces\Repository\OfficeRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

class OfficeControllerTest extends ApiTestCase
{
    private const TEST_API_KEY = 'AllowedToViewOffices';

    public OfficeRepository|MockInterface $officeRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->officeRepositoryMock = Mockery::mock(OfficeRepository::class);
        $this->instance(OfficeRepository::class, $this->officeRepositoryMock);
    }

    public function test_it_returns_unauthorized_when_not_passed_a_key()
    {
        $this->setApiKeys();
        $this->getJson(route('api.v2.admin.config.offices'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_it_returns_unauthorized_when_passed_an_incorrect_key()
    {
        $this->setApiKeys();
        $this->withHeader('Authorization', 'Bearer 11111111')
            ->getJson(route('api.v2.admin.config.offices'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_getids_returns_office_ids(): void
    {
        $this->officeRepositoryMock
            ->expects('getAllOfficeIds')
            ->once()
            ->andReturn([1, 2, 3]);

        $this->setApiKeys();

        $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->getJson(route('api.v2.admin.config.offices'))
            ->assertOk()
            ->assertExactJson([1, 2, 3]);
    }

    public function test_getids_handles_fatal_error(): void
    {
        $this->officeRepositoryMock
            ->expects('getAllOfficeIds')
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        $this->setApiKeys();

        $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->getJson(route('api.v2.admin.config.offices'))
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJsonPath('errors.0.title', '500 Internal Server Error');
    }

    protected function setApiKeys()
    {
        Config::set(
            'keyauthentication.apiKeys',
            [self::TEST_API_KEY => ['permissions' => 'api.v2.admin.config.offices']]
        );
    }
}
