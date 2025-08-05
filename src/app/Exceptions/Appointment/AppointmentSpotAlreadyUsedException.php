<?php

namespace App\Exceptions\Appointment;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Throwable;

class AppointmentSpotAlreadyUsedException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::UNPROCESSABLE_ENTITY;
    public const ERROR_MESSAGE = 'Spot already used';

    /**
     * @param string $message
     * @param array<string, string> $headers
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = self::ERROR_MESSAGE,
        array $headers = [],
        Throwable|null $previous = null
    ) {
        parent::__construct($message, $headers, $previous);

        $this->message = !empty($message) ? $message : __('exceptions.cant_create_appointment');
    }
}
