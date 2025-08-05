<?php

namespace Tests\Unit\Services\Auth0;

use App\Exceptions\Auth0\ApiRequestFailedException;
use App\Services\Auth0\UserService;
use App\Services\LogService;
use Aptive\Component\Http\HttpStatus;
use Auth0\SDK\Contract\API\Management\JobsInterface;
use Auth0\SDK\Contract\API\Management\UsersByEmailInterface;
use Auth0\SDK\Contract\API\ManagementInterface;
use Auth0\SDK\Contract\Auth0Interface;
use Auth0\SDK\Exception\Auth0Exception;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

class UserServiceTest extends TestCase
{
    private const EMAIL = 'test@exampple.com';
    private const ID = 'auth0|1234567890';

    public function test_registered_email_check_throws_exception_on_network_failure(): void
    {
        Log::expects('debug')
            ->never()
            ->withAnyArgs();

        $exception = $this->createMock(Auth0Exception::class);
        $auth0Mock = $this->mockAuth0Client($exception);
        $userService = new UserService($auth0Mock);

        $this->expectException(ApiRequestFailedException::class);
        $userService->isRegisteredEmail(self::EMAIL);
    }

    public function test_resend_email_verification_throws_exception_on_network_failure(): void
    {
        $exception = $this->createMock(Auth0Exception::class);
        $auth0Mock = $this->mockAuth0ResendClient($exception);
        $userService = new UserService($auth0Mock);

        $this->expectException(ApiRequestFailedException::class);
        $userService->resendVerificationEmail(self::ID);
    }

    public function test_registered_email_check_throws_exception_if_status_code_is_not_ok(): void
    {
        Log::expects('debug')
            ->once()
            ->withArgs([
                LogService::AUTH0_USERS_BY_EMAIL_RESPONSE,
                [
                    'email' => self::EMAIL,
                    'status' => HttpStatus::NOT_FOUND,
                    'response' => '[]',
                ],
            ]);

        $auth0responseMock = $this->getResponseMock(HttpStatus::NOT_FOUND, '[]');
        $auth0Mock = $this->mockAuth0Client($auth0responseMock);

        $userService = new UserService($auth0Mock);

        $this->expectException(ApiRequestFailedException::class);
        $userService->isRegisteredEmail(self::EMAIL);
    }

    public function test_resend_email_verification_throws_exception_if_status_code_is_not_ok(): void
    {
        $auth0responseMock = $this->getResponseMock(HttpStatus::NOT_FOUND, '[]', false);
        $auth0Mock = $this->mockAuth0ResendClient($auth0responseMock);

        $userService = new UserService($auth0Mock);

        $this->expectException(ApiRequestFailedException::class);
        $userService->resendVerificationEmail(self::ID);
    }

    public function test_registered_email_check_throws_exception_if_invalid_json_received(): void
    {
        Log::expects('debug')
            ->once()
            ->withArgs([
                LogService::AUTH0_USERS_BY_EMAIL_RESPONSE,
                [
                    'email' => self::EMAIL,
                    'status' => HttpStatus::OK,
                    'response' => '[',
                ],
            ]);

        $auth0responseMock = $this->getResponseMock(HttpStatus::OK, '[');
        $auth0Mock = $this->mockAuth0Client($auth0responseMock);

        $userService = new UserService($auth0Mock);

        $this->expectException(ApiRequestFailedException::class);
        $userService->isRegisteredEmail(self::EMAIL);
    }

    public function test_it_resends_email_verification(): void
    {
        $auth0responseMock = $this->getResponseMock(HttpStatus::CREATED, '', false);
        $auth0Mock = $this->mockAuth0ResendClient($auth0responseMock);

        $userService = new UserService($auth0Mock);

        $this->assertEmpty($userService->resendVerificationEmail(self::ID));
    }

    /**
     * @dataProvider emailVerificationResponseProvider
     */
    public function test_it_checks_email_verification(bool $isVerifiedEmail, array $responseData): void
    {
        $responseJson = json_encode($responseData, JSON_THROW_ON_ERROR);

        Log::expects('debug')
            ->once()
            ->withArgs([
                LogService::AUTH0_USERS_BY_EMAIL_RESPONSE,
                [
                    'email' => self::EMAIL,
                    'status' => HttpStatus::OK,
                    'response' => $responseJson,
                ],
            ]);

        $auth0responseMock = $this->getResponseMock(HttpStatus::OK, $responseJson);
        $auth0Mock = $this->mockAuth0Client($auth0responseMock);

        $userService = new UserService($auth0Mock);

        $this->assertSame($isVerifiedEmail, $userService->isRegisteredEmail(self::EMAIL));
    }

    /**
     * @return iterable<string, array<string, mixed>>
     */
    public function emailVerificationResponseProvider(): iterable
    {
        yield 'Single user in response' => [
            true,
            [[]],
        ];
        yield 'Multiple users in response' => [
            true,
            [[], [], []],
        ];
        yield 'No users in response' => [
            false,
            [],
        ];
    }

    private function mockAuth0Client(ResponseInterface|Throwable $response): Auth0Interface|MockObject
    {
        $getMockingMethod = $response instanceof Throwable ? 'willThrowException' : 'willReturn';

        $usersByEmailMock = $this->createMock(UsersByEmailInterface::class);
        $usersByEmailMock
            ->expects(self::once())
            ->method('get')
            ->with(self::EMAIL)
            ->{$getMockingMethod}($response);

        $managementMock = $this->createMock(ManagementInterface::class);
        $managementMock
            ->expects(self::once())
            ->method('usersByEmail')
            ->with()
            ->willReturn($usersByEmailMock);

        $auth0Mock = $this->createMock(Auth0Interface::class);
        $auth0Mock
            ->expects(self::once())
            ->method('management')
            ->with()
            ->willReturn($managementMock);

        return $auth0Mock;
    }

    private function mockAuth0ResendClient(ResponseInterface|Throwable $response): Auth0Interface|MockObject
    {
        $getMockingMethod = $response instanceof Throwable ? 'willThrowException' : 'willReturn';

        $jobsMock = $this->createMock(JobsInterface::class);
        $jobsMock
            ->expects(self::once())
            ->method('createSendVerificationEmail')
            ->with(self::ID)
            ->{$getMockingMethod}($response);

        $managementMock = $this->createMock(ManagementInterface::class);
        $managementMock
            ->expects(self::once())
            ->method('jobs')
            ->with()
            ->willReturn($jobsMock);

        $auth0Mock = $this->createMock(Auth0Interface::class);
        $auth0Mock
            ->expects(self::once())
            ->method('management')
            ->with()
            ->willReturn($managementMock);

        return $auth0Mock;
    }

    private function getResponseMock(int $statusCode, string $contents, bool $withBody = true): ResponseInterface|MockObject
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock
            ->expects($withBody ? self::once() : self::never())
            ->method('getContents')
            ->with()
            ->willReturn($contents);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->expects($withBody ? self::exactly(2) : self::once())
            ->method('getStatusCode')
            ->with()
            ->willReturn($statusCode);
        $responseMock
            ->expects($withBody ? self::once() : self::never())
            ->method('getBody')
            ->with()
            ->willReturn($bodyMock);

        return $responseMock;
    }
}
