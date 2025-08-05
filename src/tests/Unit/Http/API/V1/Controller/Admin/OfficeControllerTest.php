<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller\Admin;

use App\Interfaces\Repository\OfficeRepository;
use Aptive\PestRoutesSDK\CredentialsRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Credentials;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class OfficeControllerTest extends TestCase
{
    private const TEST_API_KEY = '1234567';
    private const TEST_OFFICE_ID = 1;

    public OfficeRepository|MockInterface $officeRepositoryMock;
    public CredentialsRepository|MockInterface $credentialsRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->officeRepositoryMock = Mockery::mock(OfficeRepository::class);
        $this->instance(OfficeRepository::class, $this->officeRepositoryMock);

        $this->credentialsRepositoryMock = Mockery::mock(CredentialsRepository::class);
        $this->instance(CredentialsRepository::class, $this->credentialsRepositoryMock);

    }

    public function test_offices_returns_unauthorized_when_not_passed_a_key()
    {
        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => []]);
        $this->getJson(route('api.admin.config.offices'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_getCredentials_returns_unauthorized_when_not_passed_a_key()
    {
        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => []]);
        $this->getJson(route('api.admin.config.offices.pestroutesCredentials', ['officeID' => self::TEST_OFFICE_ID]))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_offices_returns_unauthorized_when_passed_an_incorrect_key()
    {
        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => []]);
        $this->withHeader('Authorization', 'Bearer 11111111')
            ->getJson(route('api.admin.config.offices'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_getCredentials_returns_unauthorized_when_passed_an_incorrect_key()
    {
        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => []]);
        $this->withHeader('Authorization', 'Bearer 11111111')
            ->getJson(route('api.admin.config.offices.pestroutesCredentials', ['officeID' => self::TEST_OFFICE_ID]))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_getids_handles_fatal_error(): void
    {
        $this->officeRepositoryMock
            ->expects('getAllOfficeIds')
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => ['permissions' => '*']]);

        $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->getJson(route('api.admin.config.offices'))
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJsonPath('errors.0.title', '500 Internal Server Error');
    }

    public function test_getCredentials_handles_fatal_error(): void
    {
        $this->credentialsRepositoryMock
            ->expects('find')
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => ['permissions' => '*']]);

        $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->getJson(route('api.admin.config.offices.pestroutesCredentials', ['officeID' => self::TEST_OFFICE_ID]))
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJsonPath('errors.0.title', '500 Internal Server Error');
    }

    /**
     * @dataProvider provideOfficePermissions
     */
    public function test_it_retrieves_list_of_configured_office_ids(string $permission, bool $result)
    {
        if ($result) {
            $this->officeRepositoryMock
            ->expects('getAllOfficeIds')
            ->once()
            ->andReturn([1, 2, 3]);
        }
        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => ['permissions' => $permission]]);

        $retrieved = $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->getJson(route('api.admin.config.offices'));

        if ($result) {
            $retrieved->assertOk()
            ->assertExactJson([1, 2, 3]);
        } else {
            $retrieved->assertStatus(Response::HTTP_UNAUTHORIZED);
        }
    }

    public function provideOfficePermissions(): array
    {
        return [
            [
                'permission' => 'config.offices.pestroutesCredentials',
                'result' => false,
            ],
            [
                'permission' => 'config.offices.pestroutesCredentials,users.list',
                'result' => false,
            ],
            [
                'permission' => 'config.offices,users.list',
                'result' => true,
            ],
            [
                'permission' => 'config.offices',
                'result' => true,
            ],
            [
                'permission' => '*',
                'result' => true,
            ],
            [
                'permission' => 'users.list',
                'result' => false,
            ],
            [
                'permission' => 'non.existing',
                'result' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideGetCredentialsPermissions
     */
    public function test_it_retrieves_list_of_configured_pestroutes_credentials_for_specified_office(string $permission, bool $result)
    {
        if ($result) {
            $this->credentialsRepositoryMock
            ->expects('find')
            ->withArgs([self::TEST_OFFICE_ID])
            ->andReturn(new Credentials(self::TEST_OFFICE_ID, 'testToken', 'testKey'));
        }
        Config::set('keyauthentication.apiKeys', [self::TEST_API_KEY => ['permissions' => $permission]]);

        $retrieved = $this->withHeader('Authorization', 'Bearer ' . self::TEST_API_KEY)
            ->getJson(route('api.admin.config.offices.pestroutesCredentials', ['officeID' => self::TEST_OFFICE_ID]));

        if ($result) {
            $retrieved->assertOk()
            ->assertExactJson([
                'authenticationKey' => 'testKey',
                'authenticationToken' => 'testToken'
            ]);
        } else {
            $retrieved->assertStatus(Response::HTTP_UNAUTHORIZED);
        }
    }

    public function provideGetCredentialsPermissions(): array
    {
        return [
            [
                'permission' => 'config.offices.pestroutesCredentials',
                'result' => true,
            ],
            [
                'permission' => 'config.offices.pestroutesCredentials,users.list',
                'result' => true,
            ],
            [
                'permission' => 'config.offices,users.list',
                'result' => false,
            ],
            [
                'permission' => 'config.offices',
                'result' => false,
            ],
            [
                'permission' => '*',
                'result' => true,
            ],
            [
                'permission' => 'users.list',
                'result' => false,
            ],
            [
                'permission' => 'non.existing',
                'result' => false,
            ],
        ];
    }
}
