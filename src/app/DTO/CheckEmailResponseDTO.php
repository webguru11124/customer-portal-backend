<?php

declare(strict_types=1);

namespace App\DTO;

use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;
use Spatie\LaravelData\Attributes\MapOutputName;

class CheckEmailResponseDTO extends BaseDTO
{
    public function __construct(
        #[MapOutputName('exists')]
        public readonly bool $exists,
        #[MapOutputName('has_logged_in')]
        public readonly bool $hasLoggedIn,
        #[MapOutputName('has_registered')]
        public readonly bool|null $hasRegistered,
        #[MapOutputName('completed_initial_service')]
        public readonly bool $completedInitialService,
        #[MapOutputName('status')]
        public readonly CustomerStatus $status
    ) {
    }
}
