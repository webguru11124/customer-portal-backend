<?php

declare(strict_types=1);

namespace App\DTO\Appointment;

use App\DTO\BaseDTO;
use App\Helpers\DateTimeHelper;
use App\Traits\HasDateStartDateEnd;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

/**
 * DTO search Appointments.
 *
 * @property AppointmentStatus[] $status
 *
 * @method getCarbonDateStart()
 * @method getCarbonDateEnd()
 */
class SearchAppointmentsDTO extends BaseDTO
{
    use HasDateStartDateEnd;

    /**
     * @param AppointmentStatus[]|null $status
     * @param int[] $accountNumber
     * @param int[] $serviceIds
     * @param int[] $ids
     * @param int[] $subscriptionIds
     *
     * @throws ValidationException
     */
    public function __construct(
        public readonly int $officeId,
        public readonly array $accountNumber = [],
        public readonly string|null $dateStart = null,
        public readonly string|null $dateEnd = null,
        public readonly string|null $dateCompletedStart = null,
        public readonly string|null $dateCompletedEnd = null,
        public readonly array|null $status = null,
        public readonly array $serviceIds = [],
        public readonly array $ids = [],
        public readonly array $subscriptionIds = [],
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        $format = DateTimeHelper::defaultDateFormat();

        return [
            'officeId' => 'required|int',
            'accountNumber.*' => 'int',
            'dateStart' => sprintf('nullable|date_format:%s', $format),
            'dateEnd' => sprintf('nullable|date_format:%s|after_or_equal:dateStart', $format),
            'dateCompletedStart' => sprintf('nullable|date_format:%s', $format),
            'dateCompletedEnd' => sprintf('nullable|date_format:%s', $format),
            'status' => 'nullable|array',
            'status.*' => new Enum(AppointmentStatus::class),
            'serviceIds' => 'nullable|array',
            'serviceIds.*' => 'int',
            'ids' => 'nullable|array',
            'ids.*' => 'int',
            'subscriptionIds' => 'nullable|array',
            'subscriptionIds.*' => 'int',
        ];
    }

    public function getCarbonDateCompletedStart(): Carbon|null
    {
        return $this->dateCompletedStart
            ? DateTimeHelper::dateToCarbon($this->dateCompletedStart)->startOfDay()
            : null;
    }

    public function getCarbonDateCompletedEnd(): Carbon|null
    {
        return $this->dateCompletedEnd
            ? DateTimeHelper::dateToCarbon($this->dateCompletedEnd)->endOfDay()
            : null;
    }
}
