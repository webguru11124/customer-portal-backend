<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

final class ConfigHelper
{
    public static function getGlobalOfficeId(): int
    {
        return (int) Config::get('pestroutes.auth.global_office_id');
    }

    public static function getReserviceTypeId(): int
    {
        return (int) Config::get('pestroutes.global_reservice_type_id');
    }

    public static function getLongReserviceInterval(): int
    {
        return (int) Config::get('aptive.long_reservice_interval');
    }

    public static function getShortReserviceInterval(): int
    {
        return (int) Config::get('aptive.short_reservice_interval');
    }

    public static function getBasicReserviceInterval(): int
    {
        return (int) Config::get('aptive.basic_reservice_interval');
    }

    /**
     * @return string[]
     */
    public static function getShortIntervalServiceTypes(): array
    {
        return Config::get('aptive.short_interval_service_types');
    }

    /**
     * @return array<string, int>
     */
    public static function getSummerIntervalServiceTypes(): array
    {
        return Config::get('aptive.summer_interval_service_types');
    }

    /**
     * @return string[]
     */
    public static function getMosquitoServiceTypes(): array
    {
        return Config::get('aptive.mosquito_service_types');
    }

    public static function getStandardTreatmentDuration(): int
    {
        return (int) Config::get('aptive.standard_treatment_duration');
    }

    public static function getReserviceTreatmentDuration(): int
    {
        return (int) Config::get('aptive.reservice_treatment_duration');
    }

    public static function getCustomerRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.customer');
    }

    public static function getServiceTypeRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.service_type');
    }

    public static function getSpotRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.spot');
    }

    public static function getEmployeeRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.employee');
    }

    public static function getRouteRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.route');
    }

    public static function getOfficeRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.office');
    }

    public static function getSubscriptionRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.subscription');
    }

    public static function getAccountSyncCountdown(): int
    {
        return (int) Config::get('cache.custom_ttl.accounts_sync_countdown');
    }

    public static function getSpotsMaxDistance(): int
    {
        return (int) config('aptive.available_spots_max_distance');
    }

    /**
     * returns an array corresponding the template
     * ['fname' => 'CXP', 'lname' => 'Scheduler'].
     *
     * @return array<string, string>
     */
    public static function getCxpSchedulerName(): array
    {
        return Config::get('aptive.cxp_scheduler_name');
    }

    public static function getServiceTypeMutualOfficeID(): int
    {
        return (int) config('aptive.service_type_mutual_office_id');
    }

    /**
     * @return string
     */
    public static function getPlanBuilderCategoryName(): string
    {
        return Config::get('planbuilder.customer_portal.category_name');
    }

    /**
     * @return string
     */
    public static function getPlanBuilderActiveStatusName(): string
    {
        return Config::get('planbuilder.customer_portal.active_status_name');
    }

    public static function getPaymentServiceTokenScheme(): string
    {
        return Config::get('payment.api_token_scheme');
    }

    /**
     * @return string
     */
    public static function getPlanBuilderLowPricingLevelName(): string
    {
        return Config::get('planbuilder.customer_portal.low_pricing_level_name');
    }

    /**
     * @return array<string, int>
     */
    public static function getCPPlans(): array
    {
        return Config::get('planbuilder.customer_portal.plans');
    }

    /**
     * @return int
     */
    public static function getPlanBuilderRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.plan_builder');
    }

    /**
     * @return int
     */
    public static function getCleoCrmRepositoryCacheTtl(): int
    {
        return (int) Config::get('cache.custom_ttl.repositories.cleo_crm');
    }
}
