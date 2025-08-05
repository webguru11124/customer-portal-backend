<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\DTO\Customer\ShowCustomerResultDTO;
use App\Enums\Resources;

class ShowCustomerResponse extends AbstractFindResponse
{
    protected function getExpectedEntityClass(): string
    {
        return ShowCustomerResultDTO::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::CUSTOMER;
    }
}
