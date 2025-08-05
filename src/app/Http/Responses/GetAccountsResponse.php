<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use App\Models\External\CustomerModel;

final class GetAccountsResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return CustomerModel::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::CUSTOMER;
    }
}
