<?php

declare(strict_types=1);

namespace App\Utilites;

/**
 * This is a temporary solution just for make possible mocking a command execution
 * It should be removed after Laravel get upgraded to version 10.
 * Then we can use Illuminate\Support\Facades\Process facade to run processes.
 */
class ShellExecutor
{
    public function run(string $command): false|null|string
    {
        return shell_exec($command);
    }
}
