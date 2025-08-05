<?php

namespace Tests\Unit\Actions\MagicLink;

use App\Exceptions\Account\AccountNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use App\MagicLink\MagicLink;
use App\Actions\MagicLink\MagicLinkGeneratorAction;

class MagicLinkGeneratorActionTest extends TestCase
{
    use RandomIntTestData;

    private const EMAIL = 'magic@link.com';
    private const HOURS = 2;
    private const TOKEN = 'eyJlIjoidGVzdG92eWFra3BsZWFzZWlnbm9yZUBnbWFpbC5jb20iLCJ4IjoxNzE0MTIzNjU5LCJzIjoiZWQ4NzMwNmUxYTMyZGY4MDJkY2RiNThhOGIxOWM0MzkifQ';

    protected MagicLinkGeneratorAction $action;

    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected MockInterface|OfficeRepository $officeRepositoryMock;
    protected MockInterface|MagicLink $magicLinkMock;

    public $officeIds = [1, 2, 3];

    public function setUp(): void
    {
        parent::setUp();

        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->officeRepositoryMock = Mockery::mock(OfficeRepository::class);
        $this->magicLinkMock = Mockery::mock(MagicLink::class);

        $this->action = new MagicLinkGeneratorAction(
            $this->customerRepositoryMock,
            $this->officeRepositoryMock,
            $this->magicLinkMock
        );
    }

    public function test_it_returns_token_for_existing_pest_routes_user(): void
    {
        $this->officeRepositoryMock->shouldReceive('getAllOfficeIds')
            ->once()
            ->andReturn($this->officeIds);

        $customerData = [['customerID' => $this->getTestAccountNumber()]];
        $customers = CustomerData::getTestEntityData(count($customerData), ...$customerData);
        $this->customerRepositoryMock->shouldReceive('searchActiveCustomersByEmail')
            ->with(self::EMAIL, $this->officeIds, null)
            ->once()
            ->andReturn($customers);

        $this->magicLinkMock->shouldReceive('encode')
            ->with(self::EMAIL, self::HOURS)
            ->andReturn(self::TOKEN);

        $result = ($this->action)(self::EMAIL, self::HOURS);

        self::assertEquals(self::TOKEN, $result);
    }

    public function test_it_throws_exception_for_not_existing_pest_routes_user(): void
    {
        $this->officeRepositoryMock->shouldReceive('getAllOfficeIds')
            ->once()
            ->andReturn($this->officeIds);

        $this->customerRepositoryMock->shouldReceive('searchActiveCustomersByEmail')
            ->with(self::EMAIL, $this->officeIds, null)
            ->once()
            ->andReturn(new Collection());

        $this->magicLinkMock->shouldReceive('encode')
            ->never();

        $this->expectException(AccountNotFoundException::class);
        ($this->action)(self::EMAIL, self::HOURS);
    }
}
