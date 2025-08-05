<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

class AppointmentControllerWithCustomerRouteTest extends AppointmentControllerTest
{
    protected function getCancelRoute(): string
    {
        return route('api.customer.appointments.cancel', [
            'accountNumber' => $this->getTestAccountNumber(),
            'appointmentId' => $this->getTestAppointmentId(),
        ]);
    }
}
