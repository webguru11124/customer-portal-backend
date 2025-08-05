<?php

namespace App\Services;

use App\DTO\CreatePaymentProfileDTO;
use App\DTO\CreditCardAuthorizationDTO;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Interfaces\Repository\CreditCardAuthorizationRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Models\External\CustomerModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;

/**
 * Handles Credit Card communication with repositories.
 */
class CreditCardService
{
    public function __construct(
        protected CreditCardAuthorizationRepository $creditCardAuthorizationRepository,
        protected PaymentProfileRepository $paymentProfileRepository,
        protected AccountService $accountService,
        protected TransactionSetupRepository $transactionSetupRepository,
        protected CustomerRepository $customerRepository,
    ) {
    }

    /**
     * Create a new Credit Card profile from payment token.
     *
     * @param CreatePaymentProfileDTO $createPaymentProfileDTO
     *
     * @return int
     *
     * @throws AccountNotFoundException
     * @throws CreditCardAuthorizationException
     * @throws InternalServerErrorHttpException
     * @throws AccountFrozenException
     * @throws PaymentProfileIsEmptyException
     */
    public function createPaymentProfile(CreatePaymentProfileDTO $createPaymentProfileDTO): int
    {
        $account = $this->accountService->getAccountByAccountNumber($createPaymentProfileDTO->customerId);

        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->find($account->account_number);

        if ($createPaymentProfileDTO->paymentMethod === PaymentProfilePaymentMethod::AutoPayCC) {
            $this->validateUsingAuthorization($createPaymentProfileDTO, $customer);
        }

        return $this
            ->paymentProfileRepository
            ->addPaymentProfile($customer->officeId, $createPaymentProfileDTO);
    }

    public function validateUsingAuthorization(CreatePaymentProfileDTO $createPaymentProfileDTO, CustomerModel $customer): void
    {
        $dto = new CreditCardAuthorizationDTO(
            (string) $createPaymentProfileDTO->token,
            0
        );

        //It will throw an exception if not valid
        $this->creditCardAuthorizationRepository->authorize($dto, $customer);
    }
}
