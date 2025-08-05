<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\FlexIVRApi\AppointmentRepository as FlexIVRApiAppointmentRepository;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CleoCrmRepository;
use App\Interfaces\Repository\ContractRepository;
use App\Interfaces\Repository\CreditCardAuthorizationRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\DocumentRepository;
use App\Interfaces\Repository\EmployeeRepository;
use App\Interfaces\Repository\FormRepository;
use App\Interfaces\Repository\GenericFlagAssignmentRepository;
use App\Interfaces\Repository\OfficeRepository as OfficeRepositoryInterface;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Interfaces\Repository\PaymentRepository;
use App\Interfaces\Repository\RouteRepository;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Interfaces\Repository\SubscriptionAddonRepository;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Interfaces\Repository\TicketRepository;
use App\Interfaces\Repository\TicketTemplateAddonRepository;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Interfaces\Repository\UserRepository as UserRepositoryInterface;
use App\Repositories\CleoCrm\CachedCleoCrmRepository;
use App\Repositories\Database\UserRepository as DatabaseUserRepository;
use App\Repositories\FlexIVR\AppointmentRepository as FlexAppointmentRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesCustomerRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesEmployeeRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesOfficeRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesRouteRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesServiceTypeRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesSpotRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesSubscriptionRepository;
use App\Repositories\PestRoutes\PestRoutesAppointmentRepository;
use App\Repositories\PestRoutes\PestRoutesContractRepository;
use App\Repositories\PestRoutes\PestRoutesDocumentRepository;
use App\Repositories\PestRoutes\PestRoutesFormRepository;
use App\Repositories\PestRoutes\PestRoutesGenericFlagAssignmentRepository;
use App\Repositories\PestRoutes\PestRoutesPaymentProfileRepository;
use App\Repositories\PestRoutes\PestRoutesPaymentRepository;
use App\Repositories\PestRoutes\PestRoutesSubscriptionAddonRepository;
use App\Repositories\PestRoutes\PestRoutesTicketRepository;
use App\Repositories\PestRoutes\PestRoutesTicketTemplateAddonsRepository;
use App\Repositories\WorldPay\WorldPayCreditCardAuthorizationRepository;
use App\Repositories\WorldPay\WorldPayTransactionSetupRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AppointmentRepository::class => PestRoutesAppointmentRepository::class,
        FlexIVRApiAppointmentRepository::class => FlexAppointmentRepository::class,
        CreditCardAuthorizationRepository::class => WorldPayCreditCardAuthorizationRepository::class,
        CustomerRepository::class => CachedPestRoutesCustomerRepository::class,
        DocumentRepository::class => PestRoutesDocumentRepository::class,
        EmployeeRepository::class => CachedPestRoutesEmployeeRepository::class,
        OfficeRepositoryInterface::class => CachedPestRoutesOfficeRepository::class,
        PaymentProfileRepository::class => PestRoutesPaymentProfileRepository::class,
        PaymentRepository::class => PestRoutesPaymentRepository::class,
        ServiceTypeRepository::class => CachedPestRoutesServiceTypeRepository::class,
        SpotRepository::class => CachedPestRoutesSpotRepository::class,
        SubscriptionRepository::class => CachedPestRoutesSubscriptionRepository::class,
        TicketRepository::class => PestRoutesTicketRepository::class,
        TransactionSetupRepository::class => WorldPayTransactionSetupRepository::class,
        UserRepositoryInterface::class => DatabaseUserRepository::class,
        RouteRepository::class => CachedPestRoutesRouteRepository::class,
        ContractRepository::class => PestRoutesContractRepository::class,
        FormRepository::class => PestRoutesFormRepository::class,
        GenericFlagAssignmentRepository::class => PestRoutesGenericFlagAssignmentRepository::class,
        SubscriptionAddonRepository::class => PestRoutesSubscriptionAddonRepository::class,
        TicketTemplateAddonRepository::class => PestRoutesTicketTemplateAddonsRepository::class,
        CleoCrmRepository::class => CachedCleoCrmRepository::class,
    ];

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            AppointmentRepository::class,
            CreditCardAuthorizationRepository::class,
            CustomerRepository::class,
            DocumentRepository::class,
            EmployeeRepository::class,
            OfficeRepositoryInterface::class,
            PaymentProfileRepository::class,
            PaymentRepository::class,
            ServiceTypeRepository::class,
            SpotRepository::class,
            SubscriptionRepository::class,
            TicketRepository::class,
            TransactionSetupRepository::class,
            UserRepositoryInterface::class,
            RouteRepository::class,
            ContractRepository::class,
            FormRepository::class,
            GenericFlagAssignmentRepository::class,
            SubscriptionAddonRepository::class,
            TicketTemplateAddonRepository::class,
            CleoCrmRepository::class,
        ];
    }
}
