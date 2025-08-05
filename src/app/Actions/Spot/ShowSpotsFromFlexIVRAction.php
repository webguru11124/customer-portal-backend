<?php

declare(strict_types=1);

namespace App\Actions\Spot;

use App\DTO\FlexIVR\Spot\SearchSpots;
use App\DTO\FlexIVR\Spot\Spot;
use App\Events\Spot\SpotSearched;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Repositories\FlexIVR\SpotRepository;
use App\Services\AccountService;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

/**
 * @final
 */
class ShowSpotsFromFlexIVRAction
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly CustomerRepository $customerRepository,
        private readonly SpotRepository $spotRepository
    ) {
    }

    /**
     * @param int $accountNumber
     *
     * @return Spot[]
     *
     * @throws GuzzleException
     * @throws JsonException
     * @throws EntityNotFoundException
     * @throws AccountNotFoundException
     */
    public function __invoke(int $accountNumber): array
    {
        $account = $this->accountService->getAccountByAccountNumber($accountNumber);
        $customer = $this->customerRepository->office($account->office_id)->find($account->account_number);

        $spotSearched = $this->spotRepository->getSpots(new SearchSpots(
            officeId: $customer->officeId,
            customerId: $customer->id,
            lat: $customer->latitude,
            lng: $customer->longitude,
            state: $customer->address->state,
            isInitial: false,
        ));

        SpotSearched::dispatch($accountNumber, $customer->latitude, $customer->longitude);

        return $spotSearched;
    }
}
