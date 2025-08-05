<?php

namespace App\Exceptions\Appointment;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Throwable;

/**
 * @param string $message
 */
class CannotCreateAppointmentException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::CONFLICT;
    public const INITIAL_APPOINTMENT_ERROR = 'Cannot Create Initial Appointment';

    /**
     * @param string $message
     * @param array<string, string> $headers
     * @param Throwable|null $previous
     */

    public function __construct(
        string $message = 'Cannot Create Appointment',
        array $headers = [],
        Throwable|null $previous = null
    ) {
        parent::__construct($message, $headers, $previous);
    }

}
