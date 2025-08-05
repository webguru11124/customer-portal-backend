<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\CreateAchTransactionSetupAction;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionSetupCreateAchRequest;
use App\Http\Responses\ErrorResponse;
use App\Services\LogService;
use Aptive\Component\Http\HttpStatus;
use Throwable;

class AchTransactionSetupController extends Controller
{
    public function __construct(
        public CreateAchTransactionSetupAction $createAchTransactionSetupAction,
        public LogService $logService
    ) {
    }

    /**
     * Create ACH transaction setup.
     *
     * @param TransactionSetupCreateAchRequest $request
     * @param string $accountNumber
     * @throws \Aptive\Component\JsonApi\Exceptions\ValidationException
     */
    public function store(TransactionSetupCreateAchRequest $request, string $accountNumber): null|ErrorResponse
    {
        try {
            ($this->createAchTransactionSetupAction)(
                $request->customer_id,
                $request->billing_name,
                $request->billing_address_line_1,
                $request->billing_address_line_2 ?? '',
                $request->billing_city,
                $request->billing_state,
                $request->billing_zip,
                $request->bank_name,
                $request->account_number,
                $request->routing_number,
                CheckType::from($request->check_type),
                $request->account_type === null ? null : AccountType::from($request->account_type),
                (bool) $request->auto_pay,
            );
        } catch (PaymentProfileIsEmptyException $exception) {
            $this->logService->logThrowable(LogService::CUSTOMER_ADD_ACH_INFO, $exception);

            return ErrorResponse::fromException($request, $exception, HttpStatus::UNPROCESSABLE_ENTITY);
        } catch (Throwable $th) {
            $this->logService->logThrowable(LogService::CUSTOMER_ADD_ACH_INFO, $th);

            return ErrorResponse::fromException($request, $th);
        }

        return null;
    }
}
