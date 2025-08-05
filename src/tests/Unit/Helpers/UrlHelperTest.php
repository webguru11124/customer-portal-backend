<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\UrlHelper;
use PHPUnit\Framework\TestCase;

final class UrlHelperTest extends TestCase
{
    private const TEST_URL = 'http://localhost/';

    /**
     * @dataProvider getTestUrlsDataProvider
     */
    public function test_ensureUrlEndsWithSlash(string $url): void
    {
        $this->assertSame(self::TEST_URL, UrlHelper::ensureUrlEndsWithSlash($url));
    }

    public function getTestUrlsDataProvider(): array
    {
        return [
            'regular url' => [self::TEST_URL],
            'url without slash' => [substr(self::TEST_URL, 0, -1)],
            'url with multiple slashes' => [self::TEST_URL . '///'],
        ];
    }
}
