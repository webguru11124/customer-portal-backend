<?php

declare(strict_types=1);

namespace Tests\Unit\Enums\FlexIVR;

use App\Enums\FlexIVR\AppointmentType;
use App\Models\External\ServiceTypeModel;
use PHPUnit\Framework\TestCase;

final class AppointmentTypeTest extends TestCase
{
    public function test_it_throws_exception_for_unknown_type(): void
    {
        $serviceType = new ServiceTypeModel();
        $serviceType->description = 'Unknown';

        $this->expectException(\InvalidArgumentException::class);
        AppointmentType::fromServiceType($serviceType);
    }

    /**
     * @dataProvider serviceTypeDescriptionProvider
     */
    public function test_creating_appointment_type_from_service_type(
        string $serviceTypeDescription,
        AppointmentType $expectedAppointmentType,
    ): void {
        $serviceType = new ServiceTypeModel();
        $serviceType->description = $serviceTypeDescription;

        $appointmentType = AppointmentType::fromServiceType($serviceType);

        $this->assertSame($expectedAppointmentType, $appointmentType);
    }

    public static function serviceTypeDescriptionProvider(): iterable
    {
        return [
            'Reservice' => ['Reservice', AppointmentType::RESERVICE],
            'Initial Service' => ['Initial Service', AppointmentType::INITIAL_SERVICE],
            'Quarterly Service' => ['Quarterly Service', AppointmentType::QUARTERLY_SERVICE],
            'Basic' => ['Basic', AppointmentType::BASIC],
            'Pro' => ['Pro', AppointmentType::PRO],
            'Pro Plus' => ['Pro Plus', AppointmentType::PRO_PLUS],
            'Premium' => ['Premium', AppointmentType::PREMIUM],
        ];
    }
}
