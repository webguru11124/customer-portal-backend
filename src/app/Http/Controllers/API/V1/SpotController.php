<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Actions\Spot\ShowAvailableSpotsAction;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchSpotsRequest;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\SearchSpotsResponse;
use App\Models\Account;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Throwable;

class SpotController extends Controller
{
    /**
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function search(SearchSpotsRequest $request, ShowAvailableSpotsAction $action, int $accountNumber): mixed
    {
        try {
            /** @var Account $account */
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            $result = ($action)(
                officeId: $account->office_id,
                accountNumber: $account->account_number,
                dateStart: $request->get('date_start'),
                dateEnd: $request->get('date_end')
            );

            return SearchSpotsResponse::make($request, $result);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }
}
