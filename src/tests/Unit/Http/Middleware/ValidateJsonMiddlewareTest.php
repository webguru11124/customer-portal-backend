<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ValidateJsonMiddleware;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ValidateJsonMiddlewareTest extends TestCase
{
    private const JSON_CONTENT_TYPE_HEADER = ['CONTENT_TYPE' => 'application/json'];

    /**
     * @dataProvider requestMethodsDataProvider
     */
    public function test_invalid_json(string $requestMethod): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request = new Request(server: self::JSON_CONTENT_TYPE_HEADER, content: 'Bad json content');
        $request->setMethod($requestMethod);

        $middleware = new ValidateJsonMiddleware();

        $middleware->handle($request, fn () => true);
    }

    /**
     * @dataProvider requestMethodsDataProvider
     */
    public function test_form_data_is_ignored(string $requestMethod): void
    {
        $request = new Request(server: ['CONTENT_TYPE' => 'multipart/form-data'], content: 'Regular content');
        $request->setMethod($requestMethod);

        $middleware = new ValidateJsonMiddleware();

        $middleware->handle($request, function ($req) {
            self::assertInstanceOf(Request::class, $req);
        });
    }

    /**
     * @dataProvider requestMethodsDataProvider
     */
    public function test_valid_json(string $resuestMethod): void
    {
        $request = new Request(server: self::JSON_CONTENT_TYPE_HEADER, content: '{"attr":"Valid Json"}');
        $request->setMethod($resuestMethod);

        $middleware = new ValidateJsonMiddleware();

        $middleware->handle($request, function ($req) {
            self::assertInstanceOf(Request::class, $req);
        });
    }

    /**
     * @return iterable<string[]>
     */
    public function requestMethodsDataProvider(): iterable
    {
        return [['POST'], ['PUT'], ['PATCH']];
    }
}
