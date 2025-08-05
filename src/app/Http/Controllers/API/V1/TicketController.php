<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\Ticket\ShowCustomersTicketsAction;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Responses\SearchTicketsResponse;
use App\Models\Account;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * @return mixed
     *
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function getTickets(Request $request, ShowCustomersTicketsAction $action, int $accountNumber): mixed
    {
        /** @var Account $account */
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        $collection = ($action)(
            $account->office_id,
            $account->account_number,
            (bool) $request->query('dueOnly')
        );

        return SearchTicketsResponse::make($request, $collection);
    }
}
