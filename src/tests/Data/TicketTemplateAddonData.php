<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Models\External\TicketTemplateAddonModel;
use App\Repositories\Mappers\PestRoutesTicketTemplateAddonsToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketAddon;

/**
 * @extends AbstractTestPestRoutesData<TicketAddon, TicketTemplateAddonModel>
 */
final class TicketTemplateAddonData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return TicketAddon::class;
    }

    protected static function getSignature(): array
    {
        return [
            'itemID' => random_int(100, PHP_INT_MAX),
            'ticketID' => random_int(100, PHP_INT_MAX),
            'description' => 'Test Description',
            'quantity' => 1,
            'amount' => random_int(100, PHP_INT_MAX),
            'taxable' => '1',
            'creditTo' => random_int(0, 100),
            'productID' => random_int(100, PHP_INT_MAX),
            'serviceID' => random_int(100, PHP_INT_MAX),
            'unitID' => random_int(100, PHP_INT_MAX),
            'category' => 'Specialty Pest',
        ];
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesTicketTemplateAddonsToExternalModelMapper::class;
    }
}
