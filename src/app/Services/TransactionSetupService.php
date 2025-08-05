<?php

namespace App\Services;

use App\DTO\InitiateTransactionSetupDTO;
use App\Enums\Models\TransactionSetupStatus;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Models\TransactionSetup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Handles Transaction Setup communication with repositories.
 */
class TransactionSetupService
{
    public function __construct(
        protected AccountService $accountService,
        protected TransactionSetupRepository $transactionSetupRepository,
    ) {
    }

    /**
     * Create a new record in the DB.
     *
     * @param InitiateTransactionSetupDTO $initiateTransactionSetupDTO
     * @return TransactionSetup
     */
    public function initiate(InitiateTransactionSetupDTO $initiateTransactionSetupDTO): TransactionSetup
    {
        return TransactionSetup::create([
            'account_number' => $initiateTransactionSetupDTO->accountNumber,
            'email' => $initiateTransactionSetupDTO->email,
            'phone_number' => $initiateTransactionSetupDTO->phoneNumber,
            'status' => TransactionSetupStatus::INITIATED,
        ]);
    }

    /**
     * Get a transaction setup by ID.
     *
     * @param string $transactionSetupID
     * @return TransactionSetup
     */
    public function findByTransactionSetupId(string $transactionSetupID): TransactionSetup
    {
        return TransactionSetup::where('transaction_setup_id', $transactionSetupID)
            ->whereIn('status', [
                TransactionSetupStatus::INITIATED,
                TransactionSetupStatus::GENERATED,
                TransactionSetupStatus::FAILED_AUTHORIZATION,
            ])
            ->firstOrFail();
    }

    public function transactionSetupIdIsComplete(int $accountNumber, string $transactionSetupId): bool
    {
        return TransactionSetup::where('account_number', $accountNumber)
            ->where('transaction_setup_id', $transactionSetupId)
            ->where('status', TransactionSetupStatus::COMPLETE)
            ->exists();
    }

    /**
     * @param int $accountNumber
     * @param string $transactionSetupId
     * @return TransactionSetup
     * @throws ModelNotFoundException
     */

    public function findGeneratedByAccountNumberAndSetupId(
        int $accountNumber,
        string $transactionSetupId
    ): TransactionSetup {
        return TransactionSetup::where('account_number', $accountNumber)
            ->where('transaction_setup_id', $transactionSetupId)
            ->whereIn('status', [
                TransactionSetupStatus::GENERATED,
            ])
            ->firstOrFail();
    }

    /**
     * Get a transaction setup by Slug.
     *
     * @param string $slug
     * @return TransactionSetup
     */
    public function findBySlug(string $slug): TransactionSetup
    {
        return TransactionSetup::where('slug', $slug)
        ->whereIn('status', [
            TransactionSetupStatus::INITIATED,
            TransactionSetupStatus::GENERATED,
            TransactionSetupStatus::FAILED_AUTHORIZATION,
            TransactionSetupStatus::EXPIRED,
        ])
        ->firstOrFail();
    }

    /**
     * Expired a Transaction Setup.
     *
     * @param TransactionSetup $transactionSetup
     * @return void
     */
    public function complete(TransactionSetup $transactionSetup): void
    {
        $transactionSetup->status = TransactionSetupStatus::COMPLETE;
        $transactionSetup->save();
    }

    /**
     * Set a Transaction Setup as Failed Authorization.
     *
     * @param TransactionSetup $transactionSetup
     * @return void
     */
    public function failAuthorization(TransactionSetup $transactionSetup): void
    {
        $transactionSetup->status = TransactionSetupStatus::FAILED_AUTHORIZATION;
        $transactionSetup->save();
    }

    /**
     * Create a unique slug.
     *
     * @return string
     */
    public function createUniqueSlug(): string
    {
        $customer = new TransactionSetup();
        $slug = null;
        while ($customer instanceof TransactionSetup) {
            $slug = $this->createSlug();
            $customer = TransactionSetup::where('slug', $slug)->first();
        }

        return $slug;
    }

    /**
     * Creates a slug
     * This method was created to be sure that function
     * that uses it can be tested.
     *
     * @return string
     */
    public function createSlug(): string
    {
        return Str::random(6);
    }
}
