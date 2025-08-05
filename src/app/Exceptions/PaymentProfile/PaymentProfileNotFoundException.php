<?php

namespace App\Exceptions\PaymentProfile;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Throwable;

class PaymentProfileNotFoundException extends AbstractHttpException
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

        $this->message = 'Payment profile not found';
    }
}
