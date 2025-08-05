<?php

namespace Tests\Unit\DTO;

use App\DTO\Check;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CheckTest extends TestCase
{
    public function test_constructor_is_locked(): void
    {
        $reflection = new ReflectionClass(Check::class);

        self::assertTrue($reflection->getConstructor()->isPrivate());
    }

    public function test_is_true(): void
    {
        $check = Check::true();

        self::assertTrue($check->isTrue());
        self::assertFalse($check->isFalse());
        self::assertNull($check->getMessage());
    }

    public function test_is_false(): void
    {
        $message = 'Test message';

        $check = Check::false($message);

        self::assertTrue($check->isFalse());
        self::assertFalse($check->isTrue());
        self::assertEquals($message, $check->getMessage());
    }
}
