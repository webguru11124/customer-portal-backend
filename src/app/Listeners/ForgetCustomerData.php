<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DTO\Customer\SearchCustomersDTO;
use App\Interfaces\AccountNumberAware;
use App\Models\Account;
use App\Models\User;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesCustomerRepository as CustomerRepository;
use App\Services\LoggerAwareTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ForgetCustomerData
{
    use LoggerAwareTrait;

    /**
     * @param Collection<int, Account>|null $userAccounts
     */
    public function __construct(
        private Collection|null $userAccounts = null
    ) {
        if (null === $this->userAccounts || 0 === $this->userAccounts->count()) {
            $this->userAccounts = $this->initializeAuthUserAccounts();
        }
    }

    public function handle(AccountNumberAware $event): void
    {
        try {
            $searchCustomerDTO = new SearchCustomersDTO(
                officeIds: $this->userAccounts ? $this->userAccounts->pluck('office_id')->toArray() : [],
                accountNumbers: $this->userAccounts ? $this->userAccounts->pluck('account_number')->toArray() : [],
                isActive: true,
            );

            $this->forgetCacheKey('search', [$searchCustomerDTO]);
            $this->forgetCacheKey('find', [$event->getAccountNumber()]);
        } catch (Throwable $exception) {
            $this->getLogger()?->error(sprintf(
                'Customer data cache was not flushed after event %s Error: %s',
                $event::class,
                $exception->getMessage()
            ));
        }
    }

    /**
     * @return Collection<int, Account>|null
     */
    public function getUserAccounts(): Collection|null
    {
        return $this->userAccounts;
    }

    /**
     * @return Collection<int, Account>|null
     */
    private function initializeAuthUserAccounts(): Collection|null
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        return $authUser?->load('accounts')->accounts;
    }

    /**
     * @param string $methodName
     * @param array<int|SearchCustomersDTO> $methodArguments
     */
    private function forgetCacheKey(string $methodName, array $methodArguments): void
    {
        $cacheTags = Cache::tags(CustomerRepository::getHashTag($methodName));
        $cacheTags->forget(CustomerRepository::buildKey($methodName, $methodArguments));
    }
}
