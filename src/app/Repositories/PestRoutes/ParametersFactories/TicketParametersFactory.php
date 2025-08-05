<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Ticket\SearchTicketsDTO;
use App\Traits\DateFilterAware;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Aptive\PestRoutesSDK\Filters\NumberFilter;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Tickets\Params\SearchTicketsParams;
use Illuminate\Support\Carbon;

class TicketParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    use DateFilterAware;

    /**
     * @param SearchTicketsDTO $searchDto
     *
     * @return SearchTicketsParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchTicketsDTO::class, $searchDto);

        $searchParams = [
            'ids' => $searchDto->ids,
            'officeIds' => [$searchDto->officeId],
            'customerIds' => [$searchDto->accountNumber],
        ];

        if ($searchDto->dueOnly) {
            $searchParams['balance'] = NumberFilter::greaterThan(0);
            $searchParams['invoiceDate'] = DateFilter::lessThanOrEqualTo(Carbon::now());
        }

        return new SearchTicketsParams(...$searchParams);
    }
}
