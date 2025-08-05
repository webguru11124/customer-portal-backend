<?php

declare(strict_types=1);

namespace App\Exceptions\PaymentProfile;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Throwable;

class TransactionSetupAlreadyCompleteException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::RESET_CONTENT;

    /**
     * @param string $message
     * @param array<string, string> $headers
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        array $headers = [],
        Throwable|null $previous = null
    ) {
        parent::__construct($message, $headers, $previous);

        $this->message = 'Transaction setup already completed';
    }
}
