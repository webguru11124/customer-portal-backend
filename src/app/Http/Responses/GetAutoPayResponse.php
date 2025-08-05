<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\DTO\Customer\AutoPayResponseDTO;
use App\Enums\Resources;

final class GetAutoPayResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return AutoPayResponseDTO::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::AUTOPAY;
    }
}
