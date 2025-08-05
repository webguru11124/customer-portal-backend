<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Spot\ShowAvailableSpotsAction;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Helpers\DateTimeHelper;
use App\Services\AccountService;
use App\Services\LoggerAwareTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PreloadSpots extends Command
{
    use LoggerAwareTrait;

    public function __construct(
        private AccountService $accountService,
        private ShowAvailableSpotsAction $showAvailableSpotsAction
    ) {
        parent::__construct();
    }

    /**
     * @var string
     */
    protected $signature = 'preload:spots {accountNumber} {dateStart} {dateEnd}';

    /**
     * @var string
     */
    protected $description = 'This command preloads available spots';

    /**
     * @throws AccountNotFoundException
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(): int
    {
        $this->validateInput();

        $accountNumber = (int) $this->argument('accountNumber');

        /** @var string $dateStart */
        $dateStart = $this->argument('dateStart');

        /** @var string $dateEnd */
        $dateEnd = $this->argument('dateEnd');

        $account = $this->accountService->getAccountByAccountNumber($accountNumber);

        ($this->showAvailableSpotsAction)(
            $account->office_id,
            $account->account_number,
            $dateStart,
            $dateEnd
        );

        return Command::SUCCESS;
    }

    private function validateInput(): void
    {
        $dateRule = sprintf('date_format:%s', DateTimeHelper::defaultDateFormat());

        Validator::validate(
            [
                'accountNumber' => (int) $this->argument('accountNumber'),
                'dateStart' => $this->argument('dateStart'),
                'dateEnd' => $this->argument('dateEnd'),
            ],
            [
                'accountNumber' => 'required|int|gt:0',
                'dateStart' => $dateRule,
                'dateEnd' => $dateRule,
            ]
        );
    }
}
