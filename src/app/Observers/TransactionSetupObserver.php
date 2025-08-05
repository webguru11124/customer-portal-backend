<?php

namespace App\Observers;

use App\Models\TransactionSetup;
use App\Services\TransactionSetupService;

class TransactionSetupObserver
{
    public function __construct(public TransactionSetupService $transactionSetupService)
    {
    }

    /**
     * Handle the TransactionSetup "creating" event.
     *
     * @param  \App\Models\TransactionSetup  $transactionSetup
     * @return void
     */
    public function creating(TransactionSetup $transactionSetup)
    {
        if (!$transactionSetup->hasSlug()) {
            $transactionSetup->slug = $this->transactionSetupService->createUniqueSlug();
        }
    }
}
