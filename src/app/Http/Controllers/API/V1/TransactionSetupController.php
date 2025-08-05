<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\CreateTransactionSetupAction;
use App\Actions\RetrieveTransactionSetupBySlugAction;
use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Exceptions\TransactionSetup\TransactionSetupExpiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionSetupCreateRequest;
use App\Models\TransactionSetup;
use Exception;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TransactionSetupController extends Controller
{
    public function show(RetrieveTransactionSetupBySlugAction $action, string $slug): TransactionSetup|Response
    {
        try {
            return response(($action)($slug));
        } catch (TransactionSetupExpiredException) {
            return response()->noContent(SymfonyResponse::HTTP_GONE);
        }
    }

    public function create(TransactionSetupCreateRequest $request, CreateTransactionSetupAction $action): TransactionSetup
    {
        try {
            $account = $request->user()->getAccountByAccountNumber($request->get('accountId'));

            return ($action)($account);
        } catch (Exception $error) {
            throw new TransactionSetupException(previous: $error);
        }
    }
}
