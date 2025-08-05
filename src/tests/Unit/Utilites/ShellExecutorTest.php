<?php

namespace Tests\Unit\Utilites;

use App\Utilites\ShellExecutor;
use PHPUnit\Framework\TestCase;

class ShellExecutorTest extends TestCase
{
    public function test_it_executes_console_commands(): void
    {
        $expected = 'TEST MESSAGE';

        $command = "echo $expected";
        $executor = new ShellExecutor();

        $result = $executor->run($command);

        self::assertStringContainsString($expected, $result);
    }
}
