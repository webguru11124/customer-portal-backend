<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use Aptive\PestRoutesSDK\Resources\Documents\Document;

final class GetDocumentsResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return Document::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::DOCUMENT;
    }
}
