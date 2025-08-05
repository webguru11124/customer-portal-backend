<?php

namespace App\Exceptions\PlanBuilder;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;

class CannotFetchPlanBuilderDataException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::UNPROCESSABLE_ENTITY;
}
