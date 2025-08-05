<?php

namespace Tests\Unit\Services;

use App\Services\LoggerAwareTrait;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class LoggerAwareTraitTest extends TestCase
{
    private const TEST_LOGGER_NAME = 'Test Logger Name';

    public function test_get_default_logger()
    {
        $traitMock = $this->getMockForTrait(LoggerAwareTrait::class);

        self::assertInstanceOf(LoggerInterface::class, $traitMock->getLogger());
    }

    public function test_it_sets_logger()
    {
        $traitMock = $this->getMockForTrait(LoggerAwareTrait::class);
        $loggerMock = $this->createMock(Logger::class);
        $loggerMock->method('getName')->willReturn(self::TEST_LOGGER_NAME);
        $traitMock->setLogger($loggerMock);

        self::assertEquals(self::TEST_LOGGER_NAME, $traitMock->getLogger()->getName());
    }
}
