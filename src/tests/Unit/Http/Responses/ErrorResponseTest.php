<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Responses;

use App\Http\Responses\ErrorResponse;
use Exception;
use Illuminate\Http\Request;
use Tests\TestCase;

final class ErrorResponseTest extends TestCase
{
    /**
     * @dataProvider errorResponseStatusProvider
     */
    public function test_it_creates_response_from_exception_without_status_code(
        ?int $statusCode = null,
    ): void {
        $request = Request::create('/');
        $exception = new Exception('Test');

        if ($statusCode !== null) {
            $response = ErrorResponse::fromException($request, $exception, $statusCode);
        } else {
            $response = ErrorResponse::fromException($request, $exception);
        }

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertSame(
            $statusCode ?? ErrorResponse::HTTP_INTERNAL_SERVER_ERROR,
            $response->getStatusCode()
        );
    }

    public function errorResponseStatusProvider(): iterable
    {
        yield 'Default status' => [null];
        yield 'Client error' => [ErrorResponse::HTTP_UNAUTHORIZED];
        yield 'Server error' => [ErrorResponse::HTTP_BAD_GATEWAY];
    }
}
