<?php

declare(strict_types=1);

namespace App\Helpers;

final class UrlHelper
{
    public static function ensureUrlEndsWithSlash(string $url): string
    {
        return rtrim($url, '/') . '/';
    }
}
