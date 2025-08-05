<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Models\External\DocumentModel;
use App\Repositories\Mappers\PestRoutesDocumentToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Documents\Document;

/**
 * @extends AbstractTestPestRoutesData<Document, DocumentModel>
 */
class DocumentData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return Document::class;
    }

    protected static function getSignature(): array
    {
        return [
            'uploadID' => random_int(10000, 99999),
            'officeID' => random_int(1, 199),
            'customerID' => random_int(199997, PHP_INT_MAX),
            'dateAdded' => '2022-09-28 07:59:20',
            'addedBy' => random_int(199997, PHP_INT_MAX),
            'description' => 'This guy is waiting for service',
            'showCustomer' => (string) random_int(0, 1),
            'showTech' => (string) random_int(0, 1),
            'appointmentID' => random_int(189987, PHP_INT_MAX),
            'prefix' => 'demoaptivepest',
            'bucket' => 'PestRoutes',
            'documentLink' => 'https://s3.amazonaws.com/PestRoutes/demoaptivepest-document-9665805.jpg?AWSAccessKeyId=AKIAWQ3J6Y5VCX64DCHN&Expires=1666099813&Signature=VJYS1ypsE6of%2Bj2lRw909PgZBGQ%3D',
        ];
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesDocumentToExternalModelMapper::class;
    }
}
