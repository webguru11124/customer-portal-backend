<?php

namespace App\Http\Controllers\API\V2;

use App\Actions\Customer\ShowCustomerActionV2;
use App\Actions\Subscription\ShowSubscriptionsAction;
use App\Actions\Ticket\ShowCustomersTicketsAction;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\Enums\Resources;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCommunicationPreferencesRequest;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\V2\ShowCustomerResponse;
use App\Services\AccountService;
use App\Services\CustomerService;
use App\Services\UserService;
use Aptive\Illuminate\Http\JsonApi\ResourceUpdatedResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;
use Exception;

class CustomerController extends Controller
{
    public function __construct(
        public readonly AccountService $accountService,
        public readonly CustomerService $customerService,
        private readonly UserService $userService
    ) {
    }

    public function getCustomerData(
        Request $request,
        int $accountNumber,
        ShowSubscriptionsAction $showSubscriptionsAction,
        ShowCustomersTicketsAction $showCustomersTicketsAction,
        CustomerService $customerService
    ): mixed {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        try {
            $subscriptionData = $showSubscriptionsAction($account);

            $modifiedSubscriptionData = [];

            foreach ($subscriptionData as $subscription) {
                if ($subscription !== null && method_exists($subscription, 'toArray')) {
                    $subscriptionArray = $subscription->toArray();
                    if (isset($subscription->relatedObjects['serviceType'])) {
                        $serviceType = $subscription->relatedObjects['serviceType']->description ?? null;
                        $subscriptionArray['serviceType'] = $serviceType;
                    }
                    $modifiedSubscriptionData[] = $subscriptionArray;
                }
            }

            $subscriptionsData = [
                "status" => "ok",
                "data" => $modifiedSubscriptionData
            ];

        } catch (Exception $e) {
            $subscriptionsData = ['status' => 'error', 'data' => []];
        }

        try {
            $invoiceData = $showCustomersTicketsAction($account->office_id, $account->account_number, true);
            $invoicesData = [
                "status" => "ok",
                "data" => $invoiceData
            ];
        } catch (Exception $e) {
            $invoicesData = ['status' => 'error', 'data' => []];
        }

        try {
            $this->userService->updateUserAccounts($request->user());
            $activeCustomersCollection = $customerService->getActiveCustomersCollectionForUser($request->user());
            $accountData = [
                "status" => "ok",
                "data" => $activeCustomersCollection
            ];
        } catch (Exception $e) {
            $accountData = ['status' => 'error', 'data' => []];
        }

        // AutoPay Data
        try {
            $customerAutoPayData = $customerService->getCustomerAutoPayData($account, true);
            $autoPayData = [
                "status" => "ok",
                "data" => $customerAutoPayData
            ];
        } catch (Exception $e) {
            $autoPayData = ['status' => 'error', 'data' => []];
        }

        // Assemble and return the response
        $response = [
            "links" => ["self" => $request->getPathInfo()],
            "data" => [
                "subscription" => $subscriptionsData,
                "account" => $accountData,
                "invoice" => $invoicesData,
                "autoPay" => $autoPayData
            ]
        ];
        return response()->json($response);
    }


    public function show(Request $request, int $accountNumber, ShowCustomerActionV2 $showCustomerAction): mixed
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
