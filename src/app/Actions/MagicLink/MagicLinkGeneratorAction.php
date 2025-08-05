<?php

declare(strict_types=1);

namespace App\Actions\MagicLink;

use App\Exceptions\Account\AccountNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\MagicLink\MagicLink;
use App\Models\External\CustomerModel;
use Illuminate\Support\Collection;

class MagicLinkGeneratorAction
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly OfficeRepository $officeRepository,
        private readonly MagicLink $generator,
    ) {
    }

    /**
     * @param string $email
     * @param int|null $hours
     * @return string
     * @throws AccountNotFoundException
     */
    public function __invoke(string $email, int|null $hours = null): string
    {
        $officeIDs = $this->officeRepository->getAllOfficeIds();

        /** @var Collection<int, CustomerModel> $customers */
        $customers = $this->customerRepository->searchActiveCustomersByEmail($email, $officeIDs, null);

        if ($customers->isNotEmpty()) {
            return $this->generator->encode($email, $hours);
        }

        throw new AccountNotFoundException('Account not found for the email');
    }
}
