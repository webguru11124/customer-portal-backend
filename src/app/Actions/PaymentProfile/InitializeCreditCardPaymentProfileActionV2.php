<?php

declare(strict_types=1);

namespace App\Actions\PaymentProfile;

use App\DTO\Payment\AutoPayStatusRequestDTO;
use App\DTO\Payment\CreatePaymentProfileRequestDTO;
use App\DTO\Payment\PaymentProfile;
use App\DTO\Payment\ValidateCreditCardTokenRequestDTO;
use App\Enums\Models\Payment\PaymentGateway;
use App\Enums\Models\PaymentProfile\CardType;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Events\PaymentMethod\CcAdded;
use App\Exceptions\Account\CleoCrmAccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Payment\CreditCardTokenNotFoundException;
use App\Http\Requests\V2\InitializeCreditCardPaymentProfileRequest;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Validation\ValidationException;

class InitializeCreditCardPaymentProfileActionV2
{
    public function __construct(
        private readonly AptivePaymentRepository $paymentRepository,
        private readonly CustomerRepository $customerRepository,
        public PaymentGateway $gateway = PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID
    ) {
    }

    /**
     * @throws GuzzleException
     * @throws EntityNotFoundException
     * @throws \JsonException
     * @throws ValidationException
     * @throws CreditCardTokenNotFoundException
     * @throws CleoCrmAccountNotFoundException|\Throwable
     */
    public function __invoke(
        InitializeCreditCardPaymentProfileRequest $request,
        Account $account
    ): PaymentProfile {
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->find($account->account_number);

        $this->paymentRepository->isValidCreditCardToken(new ValidateCreditCardTokenRequestDTO(
            gateway: $this->gateway,
            officeId: $account->office_id,
            ccToken: $request->cc_token,
            ccExpirationMonth: (int) $request->cc_expiration_month,
            ccExpirationYear: (int) $request->cc_expiration_year
        ));

        $customerName = explode(' ', $request->billing_name, 2);

        $createPaymentProfileDTO = new CreatePaymentProfileRequestDTO(
            customerId: $customer->id,
            gatewayId: $this->gateway,
            type: PaymentMethod::CREDIT_CARD,
            firstName: current($customerName),
            lastName: end($customerName),
            addressLine1: $request->billing_address_line_1,
            email: $customer->email ?? '',
            city: $request->billing_city,
            province: $request->billing_state,
            postalCode: (string) $request->billing_zip,
            countryCode: $customer->billingInformation->address->countryCode,
            isAutoPay: $request->auto_pay,
            ccToken: $request->cc_token,
            ccType: CardType::tryFrom($request->cc_type),
            ccExpirationMonth: (int) $request->cc_expiration_month,
            ccExpirationYear: (int) $request->cc_expiration_year,
            ccLastFour: (string) $request->cc_last_four,
            description: $request->description,
            shouldSkipGatewayValidation: true,
        );

        $paymentProfile = $this->paymentRepository->createPaymentProfile($createPaymentProfileDTO);

        if ($request->auto_pay) {
            $this->paymentRepository->updateAutoPayStatus(new AutoPayStatusRequestDTO(
                customerId: $customer->id,
                autopayMethodId: $paymentProfile->paymentMethodId,
            ));
        }

        CcAdded::dispatch($account->account_number);

        return $paymentProfile;
    }
}
