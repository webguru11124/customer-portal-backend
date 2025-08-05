<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Instrumentation\Datadog\Instrument;
use Aptive\Component\Http\HttpStatus;
use Aptive\Illuminate\Http\JsonApi\ErrorResponse as BaseErrorResponse;
use Illuminate\Http\Request;
use Throwable;

final class ErrorResponse extends BaseErrorResponse
{
    public static function fromException(
        Request $request,
        Throwable $error,
        int $statusCode = HttpStatus::INTERNAL_SERVER_ERROR
    ): static {
        if (self::shouldNotify($statusCode)) {
            Instrument::error($error);
        }

        return parent::fromException($request, $error, $statusCode);
    }

    private static function shouldNotify(int $statusCode): bool
    {
        return $statusCode >= HttpStatus::INTERNAL_SERVER_ERROR;
    }
}
