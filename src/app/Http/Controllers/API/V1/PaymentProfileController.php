<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\PaymentProfile\CompleteCreditCardPaymentProfileAction;
use App\Actions\PaymentProfile\CreateAchPaymentProfileAction;
use App\Actions\PaymentProfile\DeletePaymentProfileAction;
use App\Actions\PaymentProfile\InitializeCreditCardPaymentProfileAction;
use App\Actions\PaymentProfile\ShowCustomerPaymentProfilesAction;
use App\DTO\CreatePaymentProfileDTO;
use App\DTO\UpdatePaymentProfileDTO;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Enums\Resources;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfilesNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetPaymentProfilesRequest;
use App\Http\Requests\PaymentProfile\CompleteCreditCardPaymentProfileRequest;
use App\Http\Requests\PaymentProfile\CreateAchPaymentProfileRequest;
use App\Http\Requests\PaymentProfile\InitializeCreditCardPaymentProfileRequest;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\GetPaymentProfilesResponse;
use App\Http\Responses\PaymentProfile\InitializeCreditCardPaymentProfileResponse;
use App\Models\External\PaymentProfileModel;
use App\Services\PaymentProfileService;
use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Aptive\Component\JsonApi\Exceptions\ValidationException as JsonValidationException;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;
use Aptive\Illuminate\Http\JsonApi\ResourceCreatedResponse;
use Aptive\Illuminate\Http\JsonApi\ResourceDeletedResponse;
use Aptive\Illuminate\Http\JsonApi\ResourceUpdatedResponse;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentProfileController extends Controller
{
    public function __construct(
        private PaymentProfileService $paymentProfileService,
    ) {
    }

    /**
     * This method should be removed at all because it almost duplicates getUserPaymentProfiles method
     * but with different data representation.
     *
     * @param GetPaymentProfilesRequest $request
     * @param int $accountNumber
     * @return array<int, array<string, mixed>>|JsonResponse
     * @throws ValidationException
     * @throws PaymentProfilesNotFoundException
     */
    public function getPaymentProfiles(
        GetPaymentProfilesRequest $request,
        ShowCustomerPaymentProfilesAction $action,
        int $accountNumber
    ): array|JsonResponse {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);
        $statuses = $request->statusesAsEnums();
        $paymentMethods = $request->paymentMethodsAsEnums();

        /** @var Collection<int, PaymentProfileModel> $paymentProfiles */
        $paymentProfiles = ($action)($account, $statuses, $paymentMethods);

        $output = [];

        foreach ($paymentProfiles as $paymentProfile) {
            $output[$paymentProfile->id] = $paymentProfile->toOldDataArray();
        }

        return $output;
    }

    /**
     * @param GetPaymentProfilesRequest $request
     * @param ShowCustomerPaymentProfilesAction $action
     * @param int $accountNumber
     *
     * @return Response
     * @throws ValidationException
     * @throws \App\Exceptions\Account\AccountNotFoundException
     * @throws \Aptive\Component\Http\Exceptions\InternalServerErrorHttpException
     * @throws JsonValidationException
     */
    public function getUserPaymentProfiles(
        GetPaymentProfilesRequest $request,
        ShowCustomerPaymentProfilesAction $action,
        int $accountNumber
    ): Response {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);
        $statuses = $request->statusesAsEnums();
        $paymentMethods = $request->paymentMethodsAsEnums();

        return GetPaymentProfilesResponse::make($request, ($action)($account, $statuses, $paymentMethods));
    }

    public function createAchPaymentProfile(
        CreateAchPaymentProfileRequest $request,
        CreateAchPaymentProfileAction $action,
        int $accountNumber
    ): Response {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);
        $dto = new CreatePaymentProfileDTO(
            customerId: $account->account_number,
            paymentMethod: PaymentProfilePaymentMethod::AutoPayACH,
            token: null,
            billingName: $request->billing_name,
            billingAddressLine1: $request->billing_address_line_1,
            billingAddressLine2: $request->billing_address_line_2,
            billingCity: $request->billing_city,
            billingState: $request->billing_state,
            billingZip: $request->billing_zip,
            bankName: $request->bank_name,
            accountNumber: $request->account_number,
            routingNumber: $request->routing_number,
            checkType: CheckType::from($request->check_type),
            accountType: AccountType::from($request->account_type),
            auto_pay: $request->auto_pay,
        );

        try {
            $paymentProfileId = ($action)($dto);
        } catch (CreditCardAuthorizationException|PaymentProfileIsEmptyException $exception) {
            return ErrorResponse::fromException($request, $exception, HttpStatus::PAYMENT_REQUIRED);
        } catch (AbstractHttpException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return ResourceCreatedResponse::make($request, Resources::PAYMENT_PROFILE->value, $paymentProfileId);
    }

    public function createCreditCardPaymentProfile(
        InitializeCreditCardPaymentProfileRequest $request,
        InitializeCreditCardPaymentProfileAction $action,
        int $accountNumber
    ): Response {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        try {
            $redirectUri = ($action)(
                billingName: $request->billing_name,
                address1: $request->billing_address_line_1,
                address2: $request->billing_address_line_2,
                city: $request->billing_city,
                state: $request->billing_state,
                zip: $request->billing_zip,
                autoPay: (bool) $request->auto_pay,
                account: $account
            );
        } catch (CreditCardAuthorizationException $exception) {
            return ErrorResponse::fromException($request, $exception, HttpStatus::PAYMENT_REQUIRED);
        } catch (AbstractHttpException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return InitializeCreditCardPaymentProfileResponse::make($redirectUri);
    }

    /**
     * @param CompleteCreditCardPaymentProfileRequest $request
     * @param CompleteCreditCardPaymentProfileAction $action
     * @param int $accountNumber
     * @param string $transactionSetupId
     * @return Response
     * @throws JsonValidationException
     */
    public function completeCreditCardPaymentProfile(
        CompleteCreditCardPaymentProfileRequest $request,
        CompleteCreditCardPaymentProfileAction $action,
        int $accountNumber,
        string $transactionSetupId
    ): Response {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        try {
            $paymentProfileId = ($action)(
                $account,
                $request->PaymentAccountID,
                $request->HostedPaymentStatus,
                $transactionSetupId
            );
        } catch (AbstractHttpException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return ResourceCreatedResponse::make($request, Resources::PAYMENT_PROFILE->value, $paymentProfileId);
    }

    public function deleteUserPaymentProfile(
        Request $request,
        DeletePaymentProfileAction $action,
        int $accountNumber,
        int $paymentProfileId
    ): Response {
        $account = $request->user()->getAccountByAccountNumber($accountNumber);

        try {
            ($action)($account, $paymentProfileId);
        } catch (PaymentProfileNotFoundException $exception) {
            return ErrorResponse::fromException($request, $exception, Response::HTTP_NOT_FOUND);
        } catch (PaymentProfileNotDeletedException $exception) {
            return ErrorResponse::fromException(
                $request,
                $exception,
                $exception->getCode() === PaymentProfileNotDeletedException::STATUS_LOCKED ?
                    Response::HTTP_CONFLICT : Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return ResourceDeletedResponse::make();
    }

    /**
     * @param Request $request
     * @param int $accountNumber
     * @param int $paymentProfileId
     * @return JsonApiResponse
     * @throws PaymentProfileNotFoundException
     * @throws \App\Exceptions\PaymentProfile\PaymentProfileNotUpdatedException
     * @throws \App\Exceptions\Authorization\UnauthorizedException
     * @throws JsonValidationException
     */
    public function updatePaymentProfile(
        Request $request,
        int $accountNumber,
        int $paymentProfileId
    ) {
        try {
            $user = $request->user();
            $dto = new UpdatePaymentProfileDTO(
                $user->getAccountByAccountNumber($accountNumber)->office_id,
                $paymentProfileId,
                $request->input('billingFName'),
                $request->input('billingLName'),
                $request->input('billingAddressLine1'),
                $request->input('billingAddressLine2'),
                $request->input('billingCity'),
                $request->input('billingState'),
                $request->input('billingZip'),
                null,
                $request->input('expMonth'),
                $request->input('expYear'),
            );
            $this->paymentProfileService->updatePaymentProfile($dto);

            return ResourceUpdatedResponse::make(
                request: $request,
                type: Resources::PAYMENT_PROFILE->value,
                id: $paymentProfileId
            );
        } catch (AbstractHttpException $exception) {
            return ErrorResponse::fromException($request, $exception, $exception->statusCode());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return ErrorResponse::fromException(
                $request,
                $exception
            );
        }
    }
}
