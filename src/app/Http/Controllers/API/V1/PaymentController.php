<?php

namespace App\Http\Controllers\API\V1;

use App\DTO\AddPaymentDTO;
use App\Enums\Models\Payment\PaymentMethod;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Payment\PaymentNotCreatedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentRequest;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentProfileModel;
use App\Services\PaymentService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        public CustomerRepository $customerRepository,
        public PaymentService $paymentService,
    ) {
    }

    /**
     * @param Request $request
     * @param int $accountNumber
     *
     * @return JsonResponse
     */
    public function getPayments(Request $request, int $accountNumber): JsonResponse
    {
        try {
            // Temporary solution
            // Should be moved to Action class
            /** @var Account $account */
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            /** @var CustomerModel $customer */
            $customer = $this->customerRepository
                ->office($account->office_id)
                ->find($account->account_number);

            return new JsonResponse($this->paymentService->getPaymentIds($customer));
        } catch (AccountFrozenException|EntityNotFoundException $exception) {
            return $this->getJson404Response($exception);
        }
    }

    /**
     * @param CreatePaymentRequest $request
     * @param int $accountNumber
     *
     * @return JsonResponse
     *
     * @throws PaymentNotCreatedException
     * @throws AccountNotFoundException
     * @throws PaymentProfileNotFoundException
     * @throws InternalServerErrorHttpException
     */
    public function createPayment(CreatePaymentRequest $request, int $accountNumber): JsonResponse
    {
        try {
            // All logic should be moved to Action class
            $user = $request->user();
            /** @var Account $account */
            $account = $user->getAccountByAccountNumber($accountNumber);

            /** @var CustomerModel $customer */
            $customer = $this->customerRepository
                ->office($account->office_id)
                ->withRelated(['paymentProfiles'])
                ->find($account->account_number);

            $paymentProfileId = (int) $request->get('payment_profile_id');

            $paymentProfile = $customer->paymentProfiles->filter(
                fn (PaymentProfileModel $paymentProfile) => $paymentProfile->id === $paymentProfileId
            )->first();

            if (empty($paymentProfile) || !$paymentProfile->isValid) {
                throw new PaymentNotCreatedException();
            }

            $dto = AddPaymentDTO::from([
                'customerId' => $customer->id,
                'paymentProfileId' => $paymentProfileId,
                'amountCents' => $request->get('amount_cents'),
                'paymentMethod' => PaymentMethod::from($request->get('payment_method')),
            ]);

            return new JsonResponse($this->paymentService->addPayment($customer, $dto));
        } catch (AccountFrozenException|EntityNotFoundException|PaymentProfileNotFoundException $exception) {
            return $this->getJson404Response($exception);
        } catch (CreditCardAuthorizationException $exception) {
            return response()->json(['message' => $exception->getMessage()], HttpStatus::PAYMENT_REQUIRED);
        }
    }

    /**
     * @param Request $request
     * @param int $accountNumber
     * @param int $paymentId
     *
     * @return JsonResponse
     */
    public function getPayment(Request $request, int $accountNumber, int $paymentId): JsonResponse
    {
        try {
            /** @var Account $account */
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            /** @var CustomerModel $customer */
            $customer = $this->customerRepository
                ->office($account->office_id)
                ->find($account->account_number);

            return new JsonResponse($this->paymentService->getPayment($customer, $paymentId));
        } catch (AccountFrozenException|EntityNotFoundException $exception) {
            return $this->getJson404Response($exception);
        }
    }

    protected function getJson404Response(Throwable $exception): JsonResponse
    {
        $message = $exception->getMessage();

        return response()->json(['message' => !empty($message) ? $message : 'Account not found'], 404);
    }
}
