<?php

namespace Tests\Unit\Providers;

use App\Providers\RepositoryServiceProvider;
use Tests\TestCase;

class RepositoryServiceProviderTest extends TestCase
{
    public function test_it_setup_bindings()
    {
        $respositoryServiceProvider = new RepositoryServiceProvider(app());
        $this->assertEquals($respositoryServiceProvider->bindings, [
            'App\Interfaces\Repository\AppointmentRepository' => 'App\Repositories\PestRoutes\PestRoutesAppointmentRepository',
            'App\Interfaces\FlexIVRApi\AppointmentRepository' => 'App\Repositories\FlexIVR\AppointmentRepository',
            'App\Interfaces\Repository\CreditCardAuthorizationRepository' => 'App\Repositories\WorldPay\WorldPayCreditCardAuthorizationRepository',
            'App\Interfaces\Repository\CustomerRepository' => 'App\Repositories\PestRoutes\Cached\CachedPestRoutesCustomerRepository',
            'App\Interfaces\Repository\DocumentRepository' => 'App\Repositories\PestRoutes\PestRoutesDocumentRepository',
            'App\Interfaces\Repository\EmployeeRepository' => 'App\Repositories\PestRoutes\Cached\CachedPestRoutesEmployeeRepository',
            'App\Interfaces\Repository\OfficeRepository' => 'App\Repositories\PestRoutes\Cached\CachedPestRoutesOfficeRepository',
            'App\Interfaces\Repository\PaymentProfileRepository' => 'App\Repositories\PestRoutes\PestRoutesPaymentProfileRepository',
            'App\Interfaces\Repository\PaymentRepository' => 'App\Repositories\PestRoutes\PestRoutesPaymentRepository',
            'App\Interfaces\Repository\ServiceTypeRepository' => 'App\Repositories\PestRoutes\Cached\CachedPestRoutesServiceTypeRepository',
            'App\Interfaces\Repository\SpotRepository' => 'App\Repositories\PestRoutes\Cached\CachedPestRoutesSpotRepository',
            'App\Interfaces\Repository\SubscriptionRepository' => 'App\Repositories\PestRoutes\Cached\CachedPestRoutesSubscriptionRepository',
            'App\Interfaces\Repository\TicketRepository' => 'App\Repositories\PestRoutes\PestRoutesTicketRepository',
            'App\Interfaces\Repository\TransactionSetupRepository' => 'App\Repositories\WorldPay\WorldPayTransactionSetupRepository',
            'App\Interfaces\Repository\UserRepository' => 'App\Repositories\Database\UserRepository',
            'App\Interfaces\Repository\RouteRepository' => 'App\Repositories\PestRoutes\Cached\CachedPestRoutesRouteRepository',
            'App\Interfaces\Repository\ContractRepository' => 'App\Repositories\PestRoutes\PestRoutesContractRepository',
            'App\Interfaces\Repository\FormRepository' => 'App\Repositories\PestRoutes\PestRoutesFormRepository',
            'App\Interfaces\Repository\GenericFlagAssignmentRepository' => 'App\Repositories\PestRoutes\PestRoutesGenericFlagAssignmentRepository',
            'App\Interfaces\Repository\SubscriptionAddonRepository' => 'App\Repositories\PestRoutes\PestRoutesSubscriptionAddonRepository',
            'App\Interfaces\Repository\TicketTemplateAddonRepository' => 'App\Repositories\PestRoutes\PestRoutesTicketTemplateAddonsRepository',
            'App\Interfaces\Repository\CleoCrmRepository' => 'App\Repositories\CleoCrm\CachedCleoCrmRepository',
        ]);
    }

    public function test_it_setup_providers()
    {
        $respositoryServiceProvider = new RepositoryServiceProvider(app());
        $this->assertEquals($respositoryServiceProvider->provides(), [
            'App\Interfaces\Repository\AppointmentRepository',
            'App\Interfaces\Repository\CreditCardAuthorizationRepository',
            'App\Interfaces\Repository\CustomerRepository',
            'App\Interfaces\Repository\DocumentRepository',
            'App\Interfaces\Repository\EmployeeRepository',
            'App\Interfaces\Repository\OfficeRepository',
            'App\Interfaces\Repository\PaymentProfileRepository',
            'App\Interfaces\Repository\PaymentRepository',
            'App\Interfaces\Repository\ServiceTypeRepository',
            'App\Interfaces\Repository\SpotRepository',
            'App\Interfaces\Repository\SubscriptionRepository',
            'App\Interfaces\Repository\TicketRepository',
            'App\Interfaces\Repository\TransactionSetupRepository',
            'App\Interfaces\Repository\UserRepository',
            'App\Interfaces\Repository\RouteRepository',
            'App\Interfaces\Repository\ContractRepository',
            'App\Interfaces\Repository\FormRepository',
            'App\Interfaces\Repository\GenericFlagAssignmentRepository',
            'App\Interfaces\Repository\SubscriptionAddonRepository',
            'App\Interfaces\Repository\TicketTemplateAddonRepository',
            'App\Interfaces\Repository\CleoCrmRepository'
        ]);
    }
}
