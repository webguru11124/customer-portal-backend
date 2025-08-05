<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\Customer\ShowCustomerResultDTO;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;
use Tests\TestCase;

final class ShowCustomerResultDTOTest extends TestCase
{
    public function test_dto_accepts_null_phone_number(): void
    {
        $dto = new ShowCustomerResultDTO(
            id: 1,
            officeId: 1,
            firstName: 'F',
            lastName: 'N',
            email: 'n@example.com',
            phoneNumber: null,
            balanceCents: 0,
            isOnMonthlyBilling: false,
            dueDate: null,
            paymentProfileId: null,
            autoPayProfileLastFour: null,
            isDueForStandardTreatment: null,
            lastTreatmentDate: null,
            status: CustomerStatus::Active,
            autoPayMethod: CustomerAutoPay::AutoPayCC
        );

        $this->assertNull($dto->phoneNumber);
        $this->assertFalse($dto->isPhoneNumberValid);
    }
}
