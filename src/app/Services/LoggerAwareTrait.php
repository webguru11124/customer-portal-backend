<?php

namespace App\Services;

use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    use PsrLoggerAwareTrait;

    public function getLogger(): LoggerInterface|null
    {
        if ($this->logger === null) {
            $this->logger = app(LoggerInterface::class);
        }

        return $this->logger;
    }
}
