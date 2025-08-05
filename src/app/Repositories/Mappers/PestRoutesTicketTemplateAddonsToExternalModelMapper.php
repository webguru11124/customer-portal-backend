<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\TicketTemplateAddonModel;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketAddon;

/**
 * @implements ExternalModelMapper<TicketAddon, TicketTemplateAddonModel>
 */
class PestRoutesTicketTemplateAddonsToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param TicketAddon $source
     *
     * @return TicketTemplateAddonModel
     */
    public function map(object $source): TicketTemplateAddonModel
    {
        return TicketTemplateAddonModel::from((array) $source);
    }
}
