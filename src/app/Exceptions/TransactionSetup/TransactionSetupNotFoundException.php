<?php

namespace App\Exceptions\TransactionSetup;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Throwable;

class TransactionSetupNotFoundException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::NOT_FOUND;

    /**
     * @param string $message
     * @param array<string, string> $headers
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'Transaction setup not found',
        array $headers = [],
        Throwable|null $previous = null
    ) {
        parent::__construct($message, $headers, $previous);
    }
}
