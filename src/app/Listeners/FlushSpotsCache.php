<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Interfaces\AccountNumberAware;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesSpotRepository;
use App\Services\AccountService;
use App\Services\LoggerAwareTrait;
use Illuminate\Support\Facades\Cache;
use Throwable;

class FlushSpotsCache
{
    use LoggerAwareTrait;

    public function __construct(
        private CustomerRepository $customerRepository,
        private AccountService $accountService
    ) {
    }

    public function handle(AccountNumberAware $event): void
    {
        try {
            $account = $this->accountService->getAccountByAccountNumber($event->getAccountNumber());

            /** @var CustomerModel $customer */
            $customer = $this->customerRepository
                ->office($account->office_id)
                ->find($account->account_number);

            $tags = [CachedPestRoutesSpotRepository::buildSearchTag($customer->latitude, $customer->longitude)];
            Cache::tags($tags)->flush();
        } catch (Throwable $exception) {
            $this->getLogger()?->error(
                'Spots cache was not flushed after event ' . $event::class
                . PHP_EOL
                . 'Error: ' . $exception->getMessage()
            );
        }
    }
}
