<?php

namespace Tests\Traits;

use BadFunctionCallException;

/**
 * @method getTestOfficeId
 * @method getTestAccountNumber
 * @method getTestRoutingNumber
 * @method getTestBillingZip
 * @method getTestAppointmentId
 * @method getTestDocumentId
 * @method getTestFormId
 * @method getTestPaymentId
 * @method getTestPaymentMethodId
 * @method getTestPaymentProfileId
 * @method getTestAutoPayPaymentProfileID
 * @method getTestSpotId
 * @method getTestServiceTypeId
 * @method getTestServiceId
 * @method getTestSubscriptionId
 * @method getTestTicketId
 * @method getTestRouteId
 * @method getTestEmployeeId
 * @method getTestSubscriptionFlagId
 * @method getTestSubscriptionAddonId
 * @method getTestProductId
 * @method getTestTicketAddonsId
 * @method getTestPlanBuilderUpgradePathFromId
 */
trait RandomIntTestData
{
    protected array $randomIntTestData = [];

    private function getPrefix(): string
    {
        return 'getTest';
    }

    private function getPropertiesWithRanges(): array
    {
        return [
            'officeId' => [1, 999],
            'accountNumber' => [1000, 9999],
            'routingNumber' => [100000000, 999999999],
            'appointmentId' => [1000, 9999],
            'documentId' => [1000, 9999],
            'paymentId' => [1000, 9999999],
            'paymentMethodId' => [1000, 9999999],
            'paymentProfileId' => [1000, 9999999],
            'autoPayPaymentProfileID' => [1000, 9999999],
            'spotId' => [1000, 9999],
            'serviceTypeId' => [1, 100],
            'subscriptionId' => [1000, 9999999],
            'ticketId' => [1000, 9999999],
            'routeId' => [1000, 9999],
            'employeeId' => [1000, 9999],
            'formId' => [1000, 9999],
            'serviceId' => [1000, 9999],
            'subscriptionFlagId' => [1, 1000],
            'subscriptionAddonId' => [1, 1000],
            'productId' => [1, 9999],
            'ticketAddonsId' => [1, 9999],
            'planBuilderUpgradePathFromId' => [1, 9999],
            'billingZip' => [11111, 99999]
        ];
    }

    public function __call(string $name, array $arguments)
    {
        $propertyName = $this->parseMethodName($name);

        if ($propertyName === null) {
            throw new BadFunctionCallException();
        }

        $propertyName = lcfirst($propertyName);

        $propertiesWithRanges = $this->getPropertiesWithRanges();

        if (empty($propertiesWithRanges[$propertyName])) {
            throw new BadFunctionCallException();
        }

        if (!empty($this->randomIntTestData[$propertyName])) {
            return $this->randomIntTestData[$propertyName];
        }

        $this->randomIntTestData[$propertyName] = is_int($propertiesWithRanges[$propertyName])
            ? $propertiesWithRanges[$propertyName]
            : random_int(...$propertiesWithRanges[$propertyName]);

        return $this->randomIntTestData[$propertyName];
    }

    private function parseMethodName(string $name): ?string
    {
        preg_match(sprintf('/^%s(\w+)$/', $this->getPrefix()), $name, $matches);

        return $matches[1] ?? null;
    }
}
