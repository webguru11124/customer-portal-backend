<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Actions\PaymentProfile\CreateAchPaymentProfileActionV2;
use App\Actions\PaymentProfile\DeletePaymentProfileActionV2;
use App\Actions\PaymentProfile\InitializeCreditCardPaymentProfileActionV2;
use App\Actions\PaymentProfile\ShowCustomerPaymentProfilesActionV2;
use App\DTO\Payment\CreatePaymentProfileRequestDTO;
use App\Enums\Models\Payment\PaymentGateway;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\PaymentService\PaymentProfile\AccountType;
use App\Enums\Resources;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Http\Requests\PaymentProfile\CreateAchPaymentProfileRequestV2;
use App\Http\Requests\V2\GetPaymentProfilesRequest;
use App\Http\Requests\V2\InitializeCreditCardPaymentProfileRequest;
use App\Http\Responses\ErrorResponse;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Services\PaymentProfileService;
use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Aptive\Illuminate\Http\JsonApi\ResourceCreatedResponse;
use Aptive\Illuminate\Http\JsonApi\ResourceDeletedResponse;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class PaymentProfileController extends \App\Http\Controllers\API\V1\PaymentProfileController
{
    public function __construct(
        private CustomerRepository $customerRepository,
        PaymentProfileService $paymentProfileService
    ) {
        parent::__construct($paymentProfileService);
    }

    public function createAptiveCreditCardPaymentProfile(
        InitializeCreditCardPaymentProfileRequest $request,
        InitializeCreditCardPaymentProfileActionV2 $action,
        int $accountNumber
    ): JsonResponse|ErrorResponse {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        try {
            return response()->json(($action)($request, $account)->toArray(), HttpStatus::CREATED);
        } catch (AbstractHttpException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        } catch (CreditCardAuthorizationException $exception) {
            return ErrorResponse::fromException($request, $exception, HttpStatus::PAYMENT_REQUIRED);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }

    public function createAchPaymentProfileV2(
        CreateAchPaymentProfileRequestV2 $request,
        CreateAchPaymentProfileActionV2 $action,
        int $accountNumber
    ): Response {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->find($account->account_number);

        $nameArray = explode(' ', $request->get('billing_name'), 2);

        $dto = new CreatePaymentProfileRequestDTO(
            customerId: $accountNumber,
            gatewayId: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID,
            type: PaymentMethod::ACH,
            firstName: $nameArray[0],
            lastName: count($nameArray) > 1 ? $nameArray[1] : '',
            addressLine1: $request->get('billing_address_line_1'),
            email: (string) $customer->email,
            city: $request->get('billing_city'),
            province: $request->get('billing_state'),
            postalCode: $request->get('billing_zip'),
            countryCode: $customer->billingInformation->address->countryCode,
            isAutoPay: (bool) $request->get('auto_pay'),
            addressLine2: $request->get('billing_address_line_2'),
            achAccountNumber: $request->get('account_number'),
            achRoutingNumber: $request->get('routing_number'),
            achAccountLastFour: substr($request->get('account_number'), -4),
            achBankName: $request->get('bank_name'),
            achAccountTypeId: AccountType::tryFrom($request->get('account_type')),
        );

        try {
            $paymentProfileId = $action($dto);
        } catch (AbstractHttpException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        } catch (CreditCardAuthorizationException $exception) {
            return ErrorResponse::fromException($request, $exception, HttpStatus::PAYMENT_REQUIRED);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return ResourceCreatedResponse::make($request, Resources::PAYMENT_PROFILE->value, $paymentProfileId);
    }

    public function deleteAptivePaymentProfile(
        Request $request,
        DeletePaymentProfileActionV2 $action,
        int $accountNumber,
        string $paymentProfileId
    ): Response {
        try {
            ($action)($accountNumber, $paymentProfileId);
        } catch (PaymentProfileNotDeletedException $exception) {
            return ErrorResponse::fromException(
                $request,
                $exception,
                $exception->getCode() === PaymentProfileNotDeletedException::STATUS_LOCKED
                    ? Response::HTTP_CONFLICT
                    : Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (AbstractHttpException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return ResourceDeletedResponse::make();
    }

    /**
     * @param GetPaymentProfilesRequest $request
     * @param ShowCustomerPaymentProfilesActionV2 $action
     * @param int $accountNumber
     *
     * @return JsonResponse|ErrorResponse
     *
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getAptiveUserPaymentProfiles(
        GetPaymentProfilesRequest $request,
        ShowCustomerPaymentProfilesActionV2 $action,
        int $accountNumber
    ): JsonResponse|ErrorResponse {
        try {
            return response()->json(
                [
                    'data' => ($action)($accountNumber, $request->statusesAsEnums()),
                ],
                HttpStatus::OK
            );
        } catch (AbstractHttpException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }
}
