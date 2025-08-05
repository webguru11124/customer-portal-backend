<?php

namespace App\Http\Responses;

use App\Enums\Resources;
use App\Helpers\DateTimeHelper;
use App\Models\External\SpotModel;

class SearchSpotsResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return SpotModel::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::SPOT;
    }

    /**
     * @inheritdoc
     */
    protected function whiteListOfResourceAttributes(): array|null
    {
        return [
            'date',
            'time',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function additionalAttributes(): array
    {
        return [
            'date' => fn (SpotModel $spot) => $spot->start->format(DateTimeHelper::defaultDateFormat()),
            'time' => fn (SpotModel $spot) => $spot->start->format('A'),
        ];
    }
}
