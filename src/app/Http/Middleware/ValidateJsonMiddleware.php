<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JsonException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ValidateJsonMiddleware
{
    private const PARSED_METHODS = ['POST', 'PUT', 'PATCH'];

    /**
     * Validates incoming request for valid json object.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!in_array($request->getMethod(), self::PARSED_METHODS)) {
            return $next($request);
        }

        if (!$request->isJson()) {
            return $next($request);
        }

        try {
            /** @var string $content */
            $content = $request->getContent();
            json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadRequestHttpException('Invalid JSON payload.', previous: $e);
        }

        return $next($request);
    }
}
