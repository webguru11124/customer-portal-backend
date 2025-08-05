<?php

namespace App\Interfaces\DTO;

use Aptive\PestRoutesSDK\Entity;
use Illuminate\Support\Collection;

/**
 * @template T of Entity
 */
interface SearchResultDto
{
    /**
     * @return Collection<int, T>
     */
    public function getObjectsCollection(): Collection;
}
