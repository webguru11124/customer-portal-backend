<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;

final class GetContractsResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return Contract::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::CONTRACT;
    }
}
