<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\LogRequestResponse;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LogRequestResponseTest extends TestCase
{
    public function test_it_logs_request(): void
    {
        $headersMock = $this->createMock(HeaderBag::class);
        $headersMock
            ->expects(self::once())
            ->method('all')
            ->with()
            ->willReturn(['host' => ['host']]);

        $requestMock = $this->createMock(Request::class);
        $requestMock->headers = $headersMock;

        $requestMock
            ->expects(self::once())
            ->method('merge')
            ->with($this->callback(fn (array $data) => isset($data['request_id'])));

        $requestMock
            ->expects(self::once())
            ->method('fullUrl')
            ->with()
            ->willReturn('https://full');

        $requestMock
            ->expects(self::once())
            ->method('query')
            ->with()
            ->willReturn(['q']);

        $requestMock
            ->expects(self::once())
            ->method('all')
            ->with()
            ->willReturn(['a']);

        Log::expects('info')->withArgs(function (string $message, array $data): bool {
            return $message === 'request_received'
                && isset($data['request']['id'])
                && $data['request']['location'] === 'https://full'
                && $data['request']['query_params'] === ['q']
                && $data['request']['body'] === ['a']
                && $data['request']['headers'] === ['host' => ['host']];
        })->once();

        $middleware = new LogRequestResponse();

        $this->assertSame(
            'test',
            $middleware->handle(
                $requestMock,
                (function (Request $request) use ($requestMock): string {
                    $this->assertSame($requestMock, $request);

                    return 'test';
                })(...)
            )
        );
    }

    /**
     * @dataProvider terminateResponseLogDataProvider
     */
    public function test_terminate_logs_response(
        SymfonyResponse|MockObject $responseMock,
        string $expectedLogMethod,
        string $unexpectedLogMethod,
        array $responseBody = []
    ): void {
        $requestMock = $this->createMock(Request::class);

        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $requestMock
            ->expects(self::once())
            ->method('get')
            ->with('request_id', null)
            ->willReturn('request_id');

        Log::expects($expectedLogMethod)->withArgs(function (string $message, array $data) use ($responseBody): bool {
            return $message === 'request_processed'
                && $data['response']['id'] === 'request_id'
                && isset($data['response']['status'])
                && $data['response']['body'] === $responseBody
                && $data['response']['headers'] === []
                && $data['response']['response_time'] > 0;
        })->once();
        Log::expects($unexpectedLogMethod)->withAnyArgs()->never();

        $middleware = new LogRequestResponse();

        $middleware->terminate($requestMock, $responseMock);
    }

    /**
     * @return iterable<SymfonyResponse|MockObject>
     */
    public function terminateResponseLogDataProvider(): iterable
    {
        $statuses = [
            ['code' => SymfonyResponse::HTTP_OK, 'expects' => 'info', 'never' => 'notice'],
            ['code' => SymfonyResponse::HTTP_BAD_REQUEST, 'expects' => 'notice', 'never' => 'info'],
            ['code' => SymfonyResponse::HTTP_UNAUTHORIZED, 'expects' => 'notice', 'never' => 'info'],
            ['code' => SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR, 'expects' => 'notice', 'never' => 'info'],
        ];

        foreach ($statuses as $status) {
            $headersMock = $this->setupResponseHeaderBagToReturnHeaders();
            $responseMock = $this->setupResponseToReturnHeadersAndStatus(
                Response::class,
                $headersMock,
                $status['code']
            );

            $responseMock
                ->expects(self::never())
                ->method('getContent');

            yield "{$status['code']} => {$status['expects']}" => [$responseMock, $status['expects'], $status['never']];
        }

        $headersMock = $this->setupResponseHeaderBagToReturnHeaders();
        $responseMock = $this->setupResponseToReturnHeadersAndStatus(JsonResponse::class, $headersMock);

        $responseMock
            ->expects(self::once())
            ->method('getData')
            ->with(true)
            ->willReturn(['data' => 1]);

        $responseMock
            ->expects(self::never())
            ->method('getContent');

        yield '200 JSON => info' => [$responseMock, 'info', 'notice', ['data' => 1]];

        $headersMock = $this->setupResponseHeaderBagToReturnHeaders();
        $responseMock = $this->setupResponseToReturnHeadersAndStatus(JsonApiResponse::class, $headersMock);

        $responseMock
            ->expects(self::once())
            ->method('getContent')
            ->with()
            ->willReturn('["data"]');

        yield '200 JSON API => info' => [$responseMock, 'info', 'notice', ['data']];

        $headersMock = $this->setupResponseHeaderBagToReturnHeaders();
        $headersMock
            ->expects(self::once())
            ->method('get')
            ->with('Content-Disposition')
            ->willReturn('stream');

        $responseMock = $this->setupResponseToReturnHeadersAndStatus(StreamedResponse::class, $headersMock);

        yield '200 Streamed Response => info' => [$responseMock, 'info', 'notice', ['data' => 'Binary file stream']];

        $headersMock = $this->setupResponseHeaderBagToReturnHeaders();
        $headersMock
            ->expects(self::once())
            ->method('get')
            ->with('Content-Disposition')
            ->willReturn('data');

        $responseMock = $this->setupResponseToReturnHeadersAndStatus(BinaryFileResponse::class, $headersMock);

        yield '200 Binary File Response => info' => [$responseMock, 'info', 'notice', ['data' => 'Binary file data']];
    }

    public function test_terminate_skips_options_request(): void
    {
        $requestMock = $this->createMock(Request::class);
        $responseMock = $this->createMock(Response::class);

        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $requestMock->expects(self::once())
            ->method('method')
            ->willReturn(Request::METHOD_OPTIONS);

        Log::expects('info')->never();
        Log::expects('notice')->never();
        $middleware = new LogRequestResponse();

        $middleware->terminate($requestMock, $responseMock);
    }

    /**
     * @return MockObject|ResponseHeaderBag|(ResponseHeaderBag&MockObject)
     */
    protected function setupResponseHeaderBagToReturnHeaders(): MockObject|ResponseHeaderBag
    {
        $headersMock = $this->createMock(ResponseHeaderBag::class);
        $headersMock
            ->expects(self::once())
            ->method('all')
            ->with()
            ->willReturn([]);
        return $headersMock;
    }

    /**
     * @param MockObject|ResponseHeaderBag $headersMock
     * @param string $className
     * @return MockObject|string
     */
    protected function setupResponseToReturnHeadersAndStatus(
        string $className,
        MockObject|ResponseHeaderBag $headersMock,
        int $status = SymfonyResponse::HTTP_OK
    ): MockObject|string {
        $responseMock = $this->createMock($className);
        $responseMock->headers = $headersMock;

        $responseMock
            ->expects(self::exactly(2))
            ->method('getStatusCode')
            ->with()
            ->willReturn($status);
        return $responseMock;
    }
}
