<?php

namespace App\Exceptions\Appointment;

use Exception;

class AppointmentNotCancelledException extends Exception
{
    /** @var string */
    protected $message = 'Appointment could not be deleted';
}
