<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use App\Models\External\SubscriptionModel;
use Illuminate\Support\Collection;

class ShowSubscriptionsAction
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    /**
     * @param Account $account
     *
     * @return Collection<int, SubscriptionModel>
     */
    public function __invoke(Account $account): Collection
    {
        return $this->subscriptionRepository
            ->office($account->office_id)
            ->withRelated(['serviceType'])
            ->searchByCustomerId([$account->account_number]);
    }
}
