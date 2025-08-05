<?php

declare(strict_types=1);

namespace App\DTO\Appointment;

use App\DTO\BaseDTO;
use DateTimeInterface;

class UpdateAppointmentDTO extends BaseDTO
{
    public function __construct(
        public int $officeId,
        public int $appointmentId,
        public int|null $routeId = null,
        public DateTimeInterface|null $start = null,
        public DateTimeInterface|null $end = null,
        public int|null $duration = null,
        public int|null $spotId = null,
        public string|null $notes = null,
        public int|null $employeeId = null,
        public int|null $subscriptionId = null,
        public int|null $typeId = null
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
            'appointmentId' => 'gt:0',
            'routeId' => 'nullable|gt:0',
            'spotId' => 'nullable|gt:0',
            'duration' => 'nullable|gt:0',
            'employeeId' => 'nullable|gt:0',
            'subscriptionId' => 'nullable|gt:0',
            'typeId' => 'nullable|gt:0',
        ];
    }
}
