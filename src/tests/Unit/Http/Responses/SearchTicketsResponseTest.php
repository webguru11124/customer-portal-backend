<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Responses;

use App\Http\Responses\SearchTicketsResponse;
use Aptive\Component\JsonApi\JsonApi;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\Data\AppointmentData;
use Tests\Data\TicketData;
use Tests\TestCase;

final class SearchTicketsResponseTest extends TestCase
{
    public function test_it_adds_appointment_date_to_response(): void
    {
        $appointment = AppointmentData::getTestEntityData()->first();
        $ticket = TicketData::getTestEntityData(1, ['appointmentId' => $appointment->id])->first();
        $ticket->setRelated('appointment', $appointment);

        $response = SearchTicketsResponse::make(Request::create('/'), new Collection([$ticket]));

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            $appointment->start->format(JsonApi::DEFAULT_DATE_FORMAT),
            $responseData['data'][0]['attributes']['appointmentDate']
        );
    }
}
