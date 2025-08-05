<?php

namespace Tests\Unit\DTO;

use App\DTO\CreatePaymentProfileDTO;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Spatie\LaravelData\Data;
use Tests\TestCase;

class CreatePaymentProfileDTOTest extends TestCase
{
    public array $address = [
        'billingName' => 'John Doe',
        'billingAddressLine1' => 'Aptive Street',
        'billingAddressLine2' => 'Unit #456',
        'billingCity' => 'Orlando',
        'billingState' => 'FL',
        'billingZip' => '32832',
    ];

    public array $validACHData = [
        'customerId' => 234567,
        'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH,
        'bankName' => 'Test Bank',
        'accountNumber' => '2550260',
        'routingNumber' => '1234567',
        'checkType' => CheckType::PERSONAL,
        'accountType' => AccountType::SAVINGS,
    ];

    public array $validCCData = [
        'customerId' => 234567,
        'paymentMethod' => PaymentProfilePaymentMethod::AutoPayCC,
        'token' => 'ABCDEFG-1234567-QWERTYUIOP',
    ];

    public function test_it_extends_base_DTO(): void
    {
        $class = new ReflectionClass(CreatePaymentProfileDTO::class);
        $this->assertTrue($class->isSubclassOf(Data::class));
    }

    /**
     * @dataProvider provideValidDTOData
     */
    public function test_it_passes_validation(array $validDTOData): void
    {
        $dto = CreatePaymentProfileDTO::from($validDTOData);
        $this->assertInstanceOf(CreatePaymentProfileDTO::class, $dto);
    }

    public function provideValidDTOData(): array
    {
        $validDTODataWithoutAccountType = array_merge($this->validACHData, $this->address, ['auto_pay' => true]);
        unset($validDTODataWithoutAccountType['accountType']);

        return [
            'Credit Card without address' => [
                'validDTOData' => array_merge($this->validCCData, ['auto_pay' => true]),
            ],
            'Credit Card with address' => [
                'validDTOData' => array_merge($this->validCCData, $this->address, ['auto_pay' => true]),
            ],
            'ACH with account type' => [
                'validDTOData' => array_merge($this->validACHData, $this->address, ['auto_pay' => true]),
            ],
            'ACH without account type' => [
                'validDTOData' => $validDTODataWithoutAccountType,
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidDTOData
     */
    public function test_it_throws_validation_error(array $invalidDTOData): void
    {
        $this->expectException(ValidationException::class);
        CreatePaymentProfileDTO::from($invalidDTOData);
    }

    public function provideInvalidDTOData(): array
    {
        $ultraLongString = 'ultra long string 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890';

        $invalidACHData = array_merge($this->validACHData, $this->address, ['auto_pay' => true]);
        $invalidData['invalid customerId'] = [$this->validCCData, 'invalid customerId'];
        $invalidData['invalid billingName'] = [$this->validCCData, 'invalid billingName'];
        $invalidData['invalid billingAddressLine1'] = [$invalidACHData, 'invalid billingAddressLine1'];
        $invalidData['invalid billingAddressLine2'] = [$invalidACHData, 'invalid billingAddressLine2'];
        $invalidData['invalid billingCity'] = [$invalidACHData, 'invalid billingCity'];
        $invalidData['invalid billingState'] = [$invalidACHData, 'invalid billingState'];
        $invalidData['invalid billingZip'] = [$invalidACHData, 'invalid billingZip'];
        $invalidData['invalid bankName'] = [$invalidACHData, 'invalid bankName'];
        $invalidData['invalid accountNumber'] = [$invalidACHData, 'invalid accountNumber'];
        $invalidData['invalid routingNumber'] = [$invalidACHData, 'invalid routingNumber'];

        $invalidData['invalid customerId'][0]['customerId'] = -1;
        $invalidData['invalid billingName'][0]['billingName'] = $ultraLongString;
        $invalidData['invalid billingAddressLine1'][0]['billingAddressLine1'] = $ultraLongString;
        $invalidData['invalid billingAddressLine2'][0]['billingAddressLine2'] = $ultraLongString;
        $invalidData['invalid billingCity'][0]['billingCity'] = $ultraLongString;
        $invalidData['invalid billingState'][0]['billingState'] = 'TEST';
        $invalidData['invalid billingZip'][0]['billingZip'] = '1';
        $invalidData['invalid bankName'][0]['bankName'] = $ultraLongString;
        $invalidData['invalid accountNumber'][0]['accountNumber'] = $ultraLongString;
        $invalidData['invalid routingNumber'][0]['routingNumber'] = $ultraLongString;
        $invalidData['no auto_pay'][0] = array_merge($this->validACHData, $this->address);
        return $invalidData;
    }
}
