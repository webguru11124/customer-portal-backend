<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Models\External\FormModel;
use App\Repositories\Mappers\PestRoutesFormToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Contracts\ContractDocumentState;
use Aptive\PestRoutesSDK\Resources\Forms\Form;

/**
 * @extends AbstractTestPestRoutesData<Form, FormModel>
 */
class FormData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return Form::class;
    }

    protected static function getSignature(): array
    {
        return [
            'formID' => random_int(999999, PHP_INT_MAX),
            'formIDs' => [random_int(999999, PHP_INT_MAX)],
            'customerID' => random_int(999999, PHP_INT_MAX),
            'dateSigned' => '2023-08-28 07:59:20',
            'dateAdded' => '2023-08-28 07:59:20',
            'unitID' => random_int(999999, PHP_INT_MAX),
            'employeeID' => random_int(999999, PHP_INT_MAX),
            'documentState' => ContractDocumentState::COMPLETED->value,
            'formTemplateID' => random_int(999999, PHP_INT_MAX),
            'formDescription' => 'Test form description',
            'documentLink' => 'https://s3.amazonaws.com/PestRoutes/demoaptivepest-document-11732074.pdf?AWSAccessKeyId=AKIAWQ3J6Y5VCX64DCHN&Expires=1695804163&Signature=1NCaq%2FyU%2FaSOfFUkKBz4RL0sBVA%3D',
        ];
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesFormToExternalModelMapper::class;
    }
}
