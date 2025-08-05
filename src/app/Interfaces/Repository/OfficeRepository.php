<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\Models\External\OfficeModel;

/**
 * @extends ExternalRepository<OfficeModel>
 */
interface OfficeRepository extends ExternalRepository
{
    /**
     * @return int[]
     */
    public function getAllOfficeIds(): array;
}
