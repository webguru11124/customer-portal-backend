<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\ServiceTypeModel;
use App\Repositories\Mappers\PestRoutesServiceTypeToExternalModelMapper;
use Aptive\PestRoutesSDK\Entity;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType;
use Illuminate\Support\Collection;

/**
 * @extends AbstractTestPestRoutesData<ServiceType, ServiceTypeModel>
 */
class ServiceTypeData extends AbstractTestPestRoutesData
{
    public const MOSQUITO = 915;
    public const PREMIUM = 2828;
    public const BASIC = 1799;
    public const PRO = 2827;
    public const PRO_PLUS = 1800;
    public const QUARTERLY_SERVICE = 21;
    public const RESERVICE = 3;

    public const SERVICE_NAMES = [
        self::MOSQUITO => 'Mosquito Service - 30 day',
        self::PREMIUM => 'Premium',
        self::BASIC => 'Basic',
        self::PRO => 'Pro',
        self::PRO_PLUS => 'Pro Plus',
        self::QUARTERLY_SERVICE => 'Quarterly Service',
        self::RESERVICE => 'Reservice',
    ];

    protected static function getSignature(): array
    {
        return [
            'typeID' => (string) self::QUARTERLY_SERVICE,
            'officeID' => '-1',
            'description' => self::SERVICE_NAMES[self::QUARTERLY_SERVICE],
            'frequency' => '90',
            'defaultCharge' => '0.00',
            'category' => 'GENERAL',
            'reservice' => '0',
            'defaultLength' => '30',
            'defaultInitialCharge' => null,
            'initialID' => '2',
            'minimumRecurringCharge' => '0.00',
            'minimumInitialCharge' => '0.00',
            'regularService' => '1',
            'initial' => '0',
            'glAccountID' => '0',
            'sentricon' => '0',
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return ServiceType::class;
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesServiceTypeToExternalModelMapper::class;
    }

    public static function getTestDataOfTypes(int ...$serviceTypeIds): Collection
    {
        return self::getTestData(
            count($serviceTypeIds),
            ...array_map(fn (int $serviceTypeId) => [
                'typeID' => $serviceTypeId,
                'description' => self::SERVICE_NAMES[$serviceTypeId],
                'reservice' => $serviceTypeId === self::RESERVICE ? '1' : '0',
            ], $serviceTypeIds)
        );
    }

    public static function getDescriptionOutput(int $serviceTypeId): ?string
    {
        $outputsArray = array_replace(self::SERVICE_NAMES, [
            self::QUARTERLY_SERVICE => 'Standard Service',
        ]);

        return $outputsArray[$serviceTypeId] ?? null;
    }

    public static function getTestEntityDataOfTypes(int ...$serviceTypeIds): Collection
    {
        /** @var ExternalModelMapper $mapper */
        $mapper = new (static::getMapperClass());

        $pestRoutesTestData = self::getTestDataOfTypes(...$serviceTypeIds);

        return $pestRoutesTestData->map(fn (Entity $pestRoutesEntity) => $mapper->map($pestRoutesEntity));
    }
}
