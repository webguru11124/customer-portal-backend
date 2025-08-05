<?php

declare(strict_types=1);

namespace App\Exceptions\Account;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Throwable;

class AccountNotFoundException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::NOT_FOUND;

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

        $this->message = !empty($message) ? $message : 'Account not found';
    }
}
