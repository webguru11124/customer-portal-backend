<?php

declare(strict_types=1);

namespace App\DTO\Route;

use App\DTO\BaseDTO;
use App\Helpers\DateTimeHelper;
use App\Traits\HasDateStartDateEnd;
use Illuminate\Validation\ValidationException;

/**
 * DTO search Routes.
 */
class SearchRoutesDTO extends BaseDTO
{
    use HasDateStartDateEnd;

    /**
     * @param int $officeId
     * @param int[] $ids
     * @param float|null $latitude
     * @param float|null $longitude
     * @param int|null $maxDistance
     * @param string|null $dateStart
     * @param string|null $dateEnd
     *
     * @throws ValidationException
     */
    public function __construct(
        public readonly int $officeId,
        public readonly array $ids = [],
        public readonly float|null $latitude = null,
        public readonly float|null $longitude = null,
        public readonly int|null $maxDistance = null,
        public readonly string|null $dateStart = null,
        public readonly string|null $dateEnd = null,
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        $format = DateTimeHelper::defaultDateFormat();

        return [
            'officeId' => 'required|integer',
            'ids' => 'nullable|array',
            'ids.*' => 'int',
            'dateStart' => sprintf('nullable|date_format:%s', $format),
            'dateEnd' => sprintf('nullable|date_format:%s|after_or_equal:dateStart', $format),
            'maxDistance' => 'nullable|int',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }
}
