<?php

namespace Tests\Traits;

use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use DateTimeImmutable;

trait GetPestRoutesAppointment
{
    private static function getPestRoutesAppointment(
        ?int $appointmentId = null,
        ?int $officeId = null,
        ?int $accountNumber = null,
        ?int $serviceTypeId = null
    ): Appointment {
        return new Appointment(
            id: $appointmentId ?? random_int(1, 197),
            officeId: $officeId ?? random_int(2, 23),
            customerId: $accountNumber ?? random_int(203, 997),
            subscriptionId: 1,
            subscriptionRegionId: 1,
            routeId: 1,
            spotId: 1,
            start: null,
            end: null,
            duration: 1,
            serviceTypeId: $serviceTypeId ?? 1,
            dateAdded: new DateTimeImmutable(),
            employeeId: 1,
            status: AppointmentStatus::Pending,
            callAhead: 0,
            isInitial: false,
            completedBy: 1,
            servicedBy: 1,
            dateCompleted: null,
            notes: null,
            officeNotes: null,
            timeIn: null,
            timeOut: null,
            checkIn: null,
            checkOut: null,
            windSpeed: null,
            windDirection: null,
            temperature: null,
            amountCollected: null,
            paymentMethod: null,
            servicedInterior: null,
            ticketId: null,
            dateCancelled: null,
            additionalTechs: [],
            cancellationReason: null,
            targetPests: [],
            appointmentNotes: null,
            doInterior: false,
            dateUpdated: new DateTimeImmutable(),
            cancelledBy: null,
            assignedTech: null,
            latIn: 1,
            latOut: 1,
            longIn: 1,
            longOut: 1,
            sequence: 1,
            lockedBy: 1,
            unitIds: []
        );
    }
}
