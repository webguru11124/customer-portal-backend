<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\TicketTemplateAddonRepository;

class TicketTemplateAddonModel extends AbstractExternalModel
{
    public int $id;
    public int $ticketId;
    public string $description;
    public int $quantity;
    public float $amount;
    public bool $isTaxable;
    public int $creditTo;
    public int $productId = 0;
    public int $serviceId = 0;
    public int|null $unitId = null;
    public mixed $category = null;
    public mixed $code = null;
    public mixed $dynamicPriceNumber = null;
    public mixed $glNumber = null;
    public mixed $unitOfMeasure = null;
    public mixed $measurementSf = null;
    public mixed $measurementLf = null;
    public mixed $qboAccountIdAr = null;
    public mixed $qboAccountIdInc = null;
    public mixed $qboAccountIdTax = null;
    public mixed $prepaymentAmount = null;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return TicketTemplateAddonRepository::class;
    }
}
