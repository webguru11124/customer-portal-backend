<?php

declare(strict_types=1);

namespace App\Interfaces;

interface AccountNumberAware
{
    public function getAccountNumber(): int;
}
