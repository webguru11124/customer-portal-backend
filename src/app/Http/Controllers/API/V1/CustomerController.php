<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\Customer\ShowCustomerAction;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\Enums\Resources;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCommunicationPreferencesRequest;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\ShowCustomerResponse;
use App\Services\AccountService;
use App\Services\CustomerService;
use Aptive\Illuminate\Http\JsonApi\ResourceUpdatedResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class CustomerController extends Controller
{
    public function __construct(
        public readonly AccountService $accountService,
        public readonly CustomerService $customerService,
    ) {
    }

    public function show(Request $request, int $accountNumber, ShowCustomerAction $showCustomerAction): mixed
    {
        $account = $this->accountService->getAccountByAccountNumber($accountNumber);

        return ShowCustomerResponse::make($request, ($showCustomerAction)($account));
    }

    public function updateCommunicationPreferences(
        UpdateCommunicationPreferencesRequest $request,
        int $accountNumber
    ): Response {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);
        $dto = new UpdateCommunicationPreferencesDTO(
            officeId: $account->office_id,
            accountNumber: $account->account_number,
            smsReminders: $request->smsReminders,
            emailReminders: $request->emailReminders,
            phoneReminders: $request->phoneReminders,
        );

        try {
            $customerId = $this->customerService->updateCommunicationPreferences($dto);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return ResourceUpdatedResponse::make($request, Resources::CUSTOMER->value, $customerId);
    }
}
