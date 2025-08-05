<?php

namespace App\Exceptions\Payment;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Throwable;

/**
 * DTO Exception.
 */
class UnknownPaymentMethodException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::UNPROCESSABLE_ENTITY;

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

        $this->message = !empty($message) ? $message : __(
            'exceptions.unknown_payment_method',
            ['method' => '']
        );
    }
}
