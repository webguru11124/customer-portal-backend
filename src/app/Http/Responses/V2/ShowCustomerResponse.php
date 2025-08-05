<?php

declare(strict_types=1);

namespace App\Http\Responses\V2;

use App\DTO\Customer\V2\ShowCustomerResultDTO;

class ShowCustomerResponse extends \App\Http\Responses\ShowCustomerResponse
{
    protected function getExpectedEntityClass(): string
    {
        return ShowCustomerResultDTO::class;
    }
}
