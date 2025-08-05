<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\OfficeRepository;
use Spatie\LaravelData\Attributes\MapOutputName;

class OfficeModel extends AbstractExternalModel
{
    public function __construct(
        public int $id,
        #[MapOutputName('office_name')] public string $officeName,
        #[MapOutputName('company_id')] public int $companyId,
        #[MapOutputName('license_number')] public string $licenseNumber,
        #[MapOutputName('contact_number')] public string $contactNumber,
        #[MapOutputName('contact_email')] public string $contactEmail,
        public string $website,
        #[MapOutputName('time_zone')] public string $timeZone,
        public string $address,
        public string $city,
        public string $state,
        public string $zip,
        #[MapOutputName('invoice_address')] public string $invoiceAddress,
        #[MapOutputName('invoice_city')] public string $invoiceCity,
        #[MapOutputName('invoice_state')] public string $invoiceState,
        #[MapOutputName('invoice_zip')] public string $invoiceZip,
    ) {
    }

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return OfficeRepository::class;
    }
}
