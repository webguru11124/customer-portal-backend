<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Attributes\MapOutputName;

final class TokenexAuthKeysRequestDTO extends BaseDTO
{
    /**
     * @throws ValidationException
     */
    public function __construct(
        #[MapOutputName('token_scheme')]
        public string $tokenScheme,
        /** @var array<int, string> $origins */
        #[MapOutputName('origins')]
        public array $origins,
        #[MapOutputName('timestamp')]
        public string $timestamp,
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        return [
            'token_scheme' => ['required', 'string'],
            'origins' => ['required', 'array'],
            'timestamp' => ['required', 'string'],
        ];
    }
}
