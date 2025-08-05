<?php

declare(strict_types=1);

namespace App\DTO\Customer\V2;

use App\DTO\Customer\ShowCustomerSubscriptionResultDTO;
use App\DTO\PlanBuilder\CurrentPlanDTO;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;

final class ShowCustomerResultDTO extends \App\DTO\Customer\ShowCustomerResultDTO
{
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
        public readonly ShowCustomerSubscriptionResultDTO|null $subscription = null,
        public CurrentPlanDTO|null $currentPlan = null,
    ) {
        parent::__construct(
            id: $id,
            officeId: $officeId,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            phoneNumber: $phoneNumber,
            balanceCents: $balanceCents,
            isOnMonthlyBilling: $isOnMonthlyBilling,
            dueDate: $dueDate,
            paymentProfileId: $paymentProfileId,
            autoPayProfileLastFour: $autoPayProfileLastFour,
            isDueForStandardTreatment: $isDueForStandardTreatment,
            lastTreatmentDate: $lastTreatmentDate,
            status: $status,
            autoPayMethod: $autoPayMethod,
            currentPlan: $currentPlan,
        );
    }
}
