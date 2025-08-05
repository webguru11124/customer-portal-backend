<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Models\External\ContractModel;
use App\Repositories\Mappers\PestRoutesContractToExternalModelMapper;
use Aptive\PestRoutesSDK\Converters\PestRoutesTypesConverter;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;
use Aptive\PestRoutesSDK\Resources\Contracts\ContractDocumentState;

/**
 * @extends AbstractTestPestRoutesData<Contract, ContractModel>
 */
class ContractData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return Contract::class;
    }

    protected static function getSignature(): array
    {
        return [
            'contractID' => random_int(999999, PHP_INT_MAX),
            'contractIDs' => PestRoutesTypesConverter::arrayToString([random_int(999999, PHP_INT_MAX)]),
            'customerIDs' => PestRoutesTypesConverter::arrayToString([random_int(999999, PHP_INT_MAX)]),
            'subscriptionIDs' => PestRoutesTypesConverter::arrayToString([random_int(999999, PHP_INT_MAX)]),
            'dateSigned' => '2023-08-28 07:59:20',
            'dateAdded' => '2023-08-28 07:59:20',
            'documentState' => ContractDocumentState::COMPLETED->value,
            'description' => 'Test contract description',
            'documentLink' => 'https://s3.amazonaws.com/PestRoutes/demoaptivepest-document-11732074.pdf?AWSAccessKeyId=AKIAWQ3J6Y5VCX64DCHN&Expires=1695804163&Signature=1NCaq%2FyU%2FaSOfFUkKBz4RL0sBVA%3D',
        ];
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesContractToExternalModelMapper::class;
    }
}
