<?php

namespace Tests\Unit\DTO\Subscription;

use App\DTO\Subscriptions\SearchSubscriptionsDTO;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class SearchSubscriptionsDTOTest extends TestCase
{
    use RandomIntTestData;

    public function test_it_can_be_created_with_valid_data(): void
    {
        $validData = $this->getValidData();

        /** @var SearchSubscriptionsDTO $dto */
        $dto = SearchSubscriptionsDTO::from($validData);

        self::assertEquals($validData['officeIds'], $dto->officeIds);
        self::assertEquals($validData['ids'], $dto->ids);
        self::assertEquals($validData['customerIds'], $dto->customerIds);
        self::assertEquals($validData['isActive'], $dto->isActive);
    }

    private function getValidData(): array
    {
        return [
            'officeIds' => [$this->getTestOfficeId(), $this->getTestOfficeId() + 1],
            'ids' => [$this->getTestSubscriptionId(), $this->getTestSubscriptionId() + 1],
            'customerIds' => [$this->getTestAccountNumber()],
            'isActive' => true,
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function test_it_throws_exception_with_invalid_data(array $data, string $expectedException): void
    {
        $this->expectException($expectedException);

        $dto = SearchSubscriptionsDTO::from($data);

        unset($dto);
    }

    /**
     * @return iterable<string|int, array<int, mixed>>
     */
    public function invalidDataProvider(): iterable
    {
        yield 'not int office ids' => [
            array_merge($this->getValidData(), [
                'officeIds' => ['officeId_1', 'officeId_2'],
            ]),
            ValidationException::class,
        ];
        yield 'not int ids' => [
            array_merge($this->getValidData(), [
                'ids' => ['id_1', 'id_2'],
            ]),
            ValidationException::class,
        ];
        yield 'not int customer ids' => [
            array_merge($this->getValidData(), [
                'ids' => ['customerId_1', 'customerId_2'],
            ]),
            ValidationException::class,
        ];
    }
}
