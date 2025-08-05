<?php

namespace App\Exceptions;

use Aptive\Component\Http\Exceptions\HttpException as AptiveHttpException;
use Aptive\Illuminate\Http\JsonApi\ErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $internalDontReport = [
        HttpResponseException::class,
        ValidationException::class,
    ];

    /**
     * @inheritdoc
     */
    protected function prepareException(Throwable $e): Throwable
    {
        $level = LogLevel::ERROR;

        if ($this->resolveStatusCode($e) < SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR) {
            $level = LogLevel::WARNING;
        }

        $this->level($e::class, $level);

        return parent::prepareException($e);
    }

    /**
     * @inheritdoc
     */
    protected function prepareJsonResponse($request, Throwable $e): JsonResponse|ErrorResponse
    {
        return match (true) {
            $e instanceof AptiveHttpException,
            $e instanceof BaseException => ErrorResponse::fromException($request, $e, $this->resolveStatusCode($e)),
            default => parent::prepareJsonResponse($request, $e)
        };
    }

    /**
     * @inheritdoc
     *
     * @return array<string, mixed>
     */
    protected function buildExceptionContext(Throwable $e): array
    {
        $request = request();

        return array_merge(
            parent::buildExceptionContext($e),
            [
                'request' => [
                    'location' => url()->full(),
                    'query_params' => $request->query(),
                    'body' => $request->all(),
                ],
            ]
        );
    }

    private function resolveStatusCode(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof HttpException => $exception->getStatusCode(),
            $exception instanceof AptiveHttpException => $exception->statusCode(),
            $exception instanceof AuthenticationException => SymfonyResponse::HTTP_UNAUTHORIZED,
            default => SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR
        };
    }
}
