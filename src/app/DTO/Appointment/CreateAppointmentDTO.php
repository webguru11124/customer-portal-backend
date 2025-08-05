<?php

declare(strict_types=1);

namespace App\DTO\Appointment;

use App\DTO\BaseDTO;
use App\Helpers\ConfigHelper;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;

/**
 * DTO search Appointments.
 */
class CreateAppointmentDTO extends BaseDTO
{
    /**
     * @throws ValidationException
     */
    public function __construct(
        public int $officeId,
        public int $accountNumber,
        public int $typeId,
        public int $routeId,
        public DateTimeInterface $start,
        public DateTimeInterface $end,
        public int $duration,
        public int|null $spotId = null,
        public string|null $notes = null,
        public int|null $employeeId = null,
        public int|null $subscriptionId = null,
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'officeId' => 'gt:0',
            'accountNumber' => 'gt:0',
            'typeId' => 'gt:0',
            'duration' => 'gt:0',
            'routeId' => 'gt:0',
            'spotId' => 'nullable|gt:0',
            'notes' => sprintf('required_if:typeId,%d|nullable|string', ConfigHelper::getReserviceTypeId()),
            'employeeId' => 'nullable|gt:0',
            'subscriptionId' => 'nullable|gt:0',
        ];
    }
}
