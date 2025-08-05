<?php

namespace App\Actions\PaymentProfile;

use App\DTO\PaymentProfile\SearchPaymentProfilesDTO;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\StatusType;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\PaymentProfileModel;
use Illuminate\Support\Collection;

class ShowCustomerPaymentProfilesAction
{
    public function __construct(
        private readonly PaymentProfileRepository $paymentProfileRepository
    ) {
    }

    /**
     * @param Account $account
     * @param StatusType[] $statuses
     * @param PaymentMethod[] $paymentMethods
     *
     * @return Collection<int, PaymentProfileModel>
     */
    public function __invoke(Account $account, array $statuses, array $paymentMethods): Collection
    {
        $searchDto = new SearchPaymentProfilesDTO(
            officeId: $account->office_id,
            accountNumbers: [$account->account_number],
            statuses: $statuses,
            paymentMethods: $paymentMethods
        );

        /** @var Collection<int, PaymentProfileModel> $result */
        $result = $this->paymentProfileRepository
            ->office($account->office_id)
            ->search($searchDto);

        return $result;
    }
}
