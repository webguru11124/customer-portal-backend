<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use Aptive\PestRoutesSDK\Resources\Forms\Form;

final class GetFormsResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return Form::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::FORM;
    }
}
