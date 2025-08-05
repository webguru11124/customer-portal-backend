<?php

namespace App\Enums\Models;

enum TransactionSetupStatus: string
{
    case INITIATED = 'initiated';
    case GENERATED = 'generated';
    case COMPLETE = 'complete';
    case FAILED_AUTHORIZATION = 'failed_authorization';
    case EXPIRED = 'expired';

    /**
     * Check if enum is INITIATED type.
     *
     * @return bool
     */
    public function isInitiated(): bool
    {
        return $this === self::INITIATED;
    }

    /**
     * Check if enum is GENERATED type.
     *
     * @return bool
     */
    public function isGenerated(): bool
    {
        return $this === self::GENERATED;
    }

    /**
     * Check if enum is COMPLETE type.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this === self::COMPLETE;
    }

    /**
     * Check if enum is FAILED_AUTHORIZATION type.
     *
     * @return bool
     */
    public function isFailedAuthorization(): bool
    {
        return $this === self::FAILED_AUTHORIZATION;
    }
}
