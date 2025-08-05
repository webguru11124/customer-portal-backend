<?php

declare(strict_types=1);

namespace App\DTO\Customer;

use App\DTO\PlanBuilder\CurrentPlanDTO;
use App\Helpers\FormatHelper;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;

class ShowCustomerResultDTO
{
    public string $name;
    public bool $isPhoneNumberValid;
    public bool $isEmailValid;
    public string $statusName;
    public bool $autoPay;

    public function __construct(
        public int $id,
        public int $officeId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string|null $phoneNumber,
        public int $balanceCents,
        public bool $isOnMonthlyBilling,
        public string|null $dueDate,
        public int|string|null $paymentProfileId,
        public string|null $autoPayProfileLastFour,
        public bool|null $isDueForStandardTreatment,
        public string|null $lastTreatmentDate,
        CustomerStatus $status,
        CustomerAutoPay $autoPayMethod,
        public CurrentPlanDTO|null $currentPlan = null,
    ) {
        $this->name = "$firstName $lastName";
        $this->isPhoneNumberValid = ($this->phoneNumber !== null) && FormatHelper::isValidPhone($this->phoneNumber);
        $this->isEmailValid = FormatHelper::isValidEmail($this->email);
        $this->statusName = $status->name;
        $this->autoPay = $autoPayMethod !== CustomerAutoPay::NotOnAutoPay;
    }
}
