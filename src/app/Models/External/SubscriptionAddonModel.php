<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\SubscriptionAddonRepository;

class SubscriptionAddonModel extends AbstractExternalModel
{
    public int $id;
    public int $productId;
    public int|null $subscriptionId = null;
    public int|null $ticketId = null;
    public int|null $serviceId = null;
    public string|null $code = null;
    public string|null $category = null;
    public float $amount = 0.00;
    public string|null $description = null;
    public bool|null $isTaxable = null;
    public int|null $creditTo = null;
    public int $quantity = 1;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return SubscriptionAddonRepository::class;
    }
}
