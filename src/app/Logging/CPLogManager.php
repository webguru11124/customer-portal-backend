<?php

namespace App\Logging;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;

/**
 * @mixin \Illuminate\Log\Logger
 */
class CPLogManager extends LogManager
{
    /**
     * System is unusable.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logContextSize($message, $context);
        parent::log($level, $message, $context);
    }


    /**
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logContextSize(string $message, array $context = []): void
    {
        if (!empty($context)) {
            try {
                $start_memory = memory_get_usage();
                $tmp = unserialize(serialize($context));
                $size = memory_get_usage() - $start_memory;
                parent::info(sprintf('%s size %d', $message, $size));
            } catch (\Exception $e) {
                parent::info(sprintf('Serialization is not allowed. Total memory used: %d', memory_get_usage()));
            }
        }
    }
}
