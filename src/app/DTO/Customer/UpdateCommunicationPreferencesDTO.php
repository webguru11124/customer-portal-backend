<?php

declare(strict_types=1);

namespace App\DTO\Customer;

use App\DTO\BaseDTO;

final class UpdateCommunicationPreferencesDTO extends BaseDTO
{
    public function __construct(
        public readonly int $officeId,
        public readonly int $accountNumber,
        public readonly bool $smsReminders,
        public readonly bool $emailReminders,
        public readonly bool $phoneReminders
    ) {
    }
}
