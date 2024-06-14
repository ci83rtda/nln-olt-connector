<?php

namespace App\Services\Uisp\V2;

class UispApi
{

    protected $connector;

    public function __construct()
    {
        $this->connector = new UispApiConnector();
    }

    public function getUsers()
    {
        return $this->connector->makeRequest('GET', 'users');
    }

    public function createUser($userData)
    {
        return $this->connector->makeRequest('POST', 'users', $userData);
    }

    public function updateUser($userId, $userData)
    {
        return $this->connector->makeRequest('PUT', "users/{$userId}", $userData);
    }

    public function deleteUser($userId)
    {
        return $this->connector->makeRequest('DELETE', "users/{$userId}");
    }

    public function getDiscoveredDevices()
    {
        return $this->connector->makeRequest('GET', 'devices/discovered');
    }

    public function postScanForNewDevices()
    {
        return $this->connector->makeRequest('POST', 'discovery/rescan');
    }

    public function createDevicesAuthorization($devices)
    {
        return $this->connector->makeRequest('POST', 'devices/authorize',$devices);
    }

    public function putAirCubeWifiConfiguration(string $device, array $data)
    {
        return $this->connector->makeRequest('PUT', "devices/aircubes/{$device}/wireless",$data);
    }

    public function putAirCubeNetworkConfiguration(string $device, array $data)
    {
        return $this->connector->makeRequest('PUT', "devices/aircubes/{$device}/network",$data);
    }

    public function getDevices()
    {
        return $this->connector->makeRequest('GET', 'devices');
    }

    public function createDevice($deviceData)
    {
        return $this->connector->makeRequest('POST', 'devices', $deviceData);
    }

    public function createBlackboxDevice($deviceData)
    {
        return $this->connector->makeRequest('POST', 'devices/blackboxes/config', $deviceData);
    }

    public function updateDevice($deviceId, $deviceData)
    {
        return $this->connector->makeRequest('PUT', "devices/{$deviceId}", $deviceData);
    }

    public function deleteDevice($deviceId)
    {
        return $this->connector->makeRequest('DELETE', "devices/{$deviceId}");
    }

    public function getDevice($deviceId, $raw = false)
    {
        return $this->connector->makeRequest('GET', "devices/{$deviceId}",[],$raw);
    }

    public function getSites($options)
    {
        return $this->connector->makeRequest('GET', 'sites', $options);
    }

    public function postDeviceConnect(array $devicesId,string $username,string $password)
    {
        $data = [
            'deviceIds' => $devicesId,
            'username'=> $username,
            'password'=> $password,
            'httpsPort'=> 443,
            'useUnstableFirmware'=> false,
            'replaceExistingBlackBox'=> false
        ];

        return $this->connector->makeRequest('POST', 'discovery/connect/ubnt', $data);
    }

    public function getDeviceConnectStatus(string $deviceId)
    {
        return $this->connector->makeRequest('GET', "discovery/status/{$deviceId}");
    }

    public function getDiscoveryStatus()
    {
        return $this->connector->makeRequest('GET', "discovery/scan-status");
    }

    public function createSite($siteData)
    {
        return $this->connector->makeRequest('POST', 'sites', $siteData);
    }

    public function updateSite($siteId, $siteData)
    {
        return $this->connector->makeRequest('PUT', "sites/{$siteId}", $siteData);
    }

    public function deleteSite($siteId)
    {
        return $this->connector->makeRequest('DELETE', "sites/{$siteId}");
    }

    public function getNetworks()
    {
        return $this->connector->makeRequest('GET', 'networks');
    }

    public function createNetwork($networkData)
    {
        return $this->connector->makeRequest('POST', 'networks', $networkData);
    }

    public function updateNetwork($networkId, $networkData)
    {
        return $this->connector->makeRequest('PUT', "networks/{$networkId}", $networkData);
    }

    public function deleteNetwork($networkId)
    {
        return $this->connector->makeRequest('DELETE', "networks/{$networkId}");
    }

    public function getCustomers()
    {
        return $this->connector->makeRequest('GET', 'customers');
    }

    public function createCustomer($customerData)
    {
        return $this->connector->makeRequest('POST', 'customers', $customerData);
    }

    public function updateCustomer($customerId, $customerData)
    {
        return $this->connector->makeRequest('PUT', "customers/{$customerId}", $customerData);
    }

    public function deleteCustomer($customerId)
    {
        return $this->connector->makeRequest('DELETE', "customers/{$customerId}");
    }

    public function getServicePlans()
    {
        return $this->connector->makeRequest('GET', 'service-plans');
    }

    public function createServicePlan($servicePlanData)
    {
        return $this->connector->makeRequest('POST', 'service-plans', $servicePlanData);
    }

    public function updateServicePlan($servicePlanId, $servicePlanData)
    {
        return $this->connector->makeRequest('PUT', "service-plans/{$servicePlanId}", $servicePlanData);
    }

    public function activateServicePlan($servicePlanId, $servicePlanData)
    {
        return $this->connector->makeRequest('PUT', "clients/services/{$servicePlanId}/activate-quoted", $servicePlanData);
    }

    public function deleteServicePlan($servicePlanId)
    {
        return $this->connector->makeRequest('DELETE', "service-plans/{$servicePlanId}");
    }

    public function getDevicesBySite($siteId)
    {
        return $this->connector->makeRequest('GET', "sites/{$siteId}/devices");
    }

    public function getCustomersByNetwork($networkId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/customers");
    }

    public function getDevicesByCustomer($customerId)
    {
        return $this->connector->makeRequest('GET', "customers/{$customerId}/devices");
    }

    public function getNetworkSites($networkId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/sites");
    }

    public function getServicePlanSites($servicePlanId)
    {
        return $this->connector->makeRequest('GET', "service-plans/{$servicePlanId}/sites");
    }

    public function getDeviceStatistics($deviceId, $startDate, $endDate)
    {
        return $this->connector->makeRequest('GET', "devices/{$deviceId}/statistics?start={$startDate}&end={$endDate}");
    }

    public function createPayment($paymentData)
    {
        return $this->connector->makeRequest('POST', 'payments', $paymentData);
    }

    public function createInvoice($invoiceData)
    {
        return $this->connector->makeRequest('POST', 'invoices', $invoiceData);
    }

    public function getNetworkCustomers($networkId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/customers");
    }

    public function getCustomerServices($customerId)
    {
        return $this->connector->makeRequest('GET', "customers/{$customerId}/services");
    }

    public function getServiceDeviceStatistics($serviceId, $startDate, $endDate)
    {
        return $this->connector->makeRequest('GET', "services/{$serviceId}/statistics?start={$startDate}&end={$endDate}");
    }

    public function createService($serviceData)
    {
        return $this->connector->makeRequest('POST', 'services', $serviceData);
    }

    public function getPayments()
    {
        return $this->connector->makeRequest('GET', 'payments');
    }

    public function getInvoices()
    {
        return $this->connector->makeRequest('GET', 'invoices');
    }

    public function getServiceUsage($serviceId, $startDate, $endDate)
    {
        return $this->connector->makeRequest('GET', "services/{$serviceId}/usage?start={$startDate}&end={$endDate}");
    }

    public function getServiceOutages($serviceId, $startDate, $endDate)
    {
        return $this->connector->makeRequest('GET', "services/{$serviceId}/outages?start={$startDate}&end={$endDate}");
    }

    public function createSubscription($subscriptionData)
    {
        return $this->connector->makeRequest('POST', 'subscriptions', $subscriptionData);
    }

    public function updateSubscription($subscriptionId, $subscriptionData)
    {
        return $this->connector->makeRequest('PUT', "subscriptions/{$subscriptionId}", $subscriptionData);
    }

    public function getServicePlansByNetwork($networkId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/service-plans");
    }

    public function getServicePlanCustomers($servicePlanId)
    {
        return $this->connector->makeRequest('GET', "service-plans/{$servicePlanId}/customers");
    }

    public function getServiceCustomers($serviceId)
    {
        return $this->connector->makeRequest('GET', "services/{$serviceId}/customers");
    }

    public function getSubscriberSessions($subscriberId)
    {
        return $this->connector->makeRequest('GET', "subscribers/{$subscriberId}/sessions");
    }

    public function getSubscriberSessionStatistics($subscriberId, $sessionId)
    {
        return $this->connector->makeRequest('GET', "subscribers/{$subscriberId}/sessions/{$sessionId}/statistics");
    }

    public function createSubscriber($subscriberData)
    {
        return $this->connector->makeRequest('POST', 'subscribers', $subscriberData);
    }

    public function getServiceUsageStatistics($serviceId, $startDate, $endDate)
    {
        return $this->connector->makeRequest('GET', "services/{$serviceId}/usage-statistics?start={$startDate}&end={$endDate}");
    }

    public function getServiceLatencyStatistics($serviceId, $startDate, $endDate)
    {
        return $this->connector->makeRequest('GET', "services/{$serviceId}/latency-statistics?start={$startDate}&end={$endDate}");
    }

    public function getSiteServicePlans($siteId)
    {
        return $this->connector->makeRequest('GET', "sites/{$siteId}/service-plans");
    }

    public function getSiteCustomers($siteId)
    {
        return $this->connector->makeRequest('GET', "sites/{$siteId}/customers");
    }

    public function getSiteDevices($siteId)
    {
        return $this->connector->makeRequest('GET', "sites/{$siteId}/devices");
    }

    public function getSiteNetworks($siteId)
    {
        return $this->connector->makeRequest('GET', "sites/{$siteId}/networks");
    }

    public function getSiteServicePlanCustomers($siteId, $servicePlanId)
    {
        return $this->connector->makeRequest('GET', "sites/{$siteId}/service-plans/{$servicePlanId}/customers");
    }

    public function getSiteServicePlanDevices($siteId, $servicePlanId)
    {
        return $this->connector->makeRequest('GET', "sites/{$siteId}/service-plans/{$servicePlanId}/devices");
    }

    public function getNetworkDevices($networkId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/devices");
    }

    public function getNetworkServicePlans($networkId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/service-plans");
    }

    public function getNetworkServicePlanCustomers($networkId, $servicePlanId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/service-plans/{$servicePlanId}/customers");
    }

    public function getNetworkServicePlanDevices($networkId, $servicePlanId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/service-plans/{$servicePlanId}/devices");
    }

    public function getServiceSiteCustomers($serviceId, $siteId)
    {
        return $this->connector->makeRequest('GET', "services/{$serviceId}/sites/{$siteId}/customers");
    }

    public function getServiceSiteDevices($serviceId, $siteId)
    {
        return $this->connector->makeRequest('GET', "services/{$serviceId}/sites/{$siteId}/devices");
    }

    public function getNetworkSiteCustomers($networkId, $siteId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/sites/{$siteId}/customers");
    }

    public function getNetworkSiteDevices($networkId, $siteId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/sites/{$siteId}/devices");
    }

    public function getNetworkSiteServicePlans($networkId, $siteId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/sites/{$siteId}/service-plans");
    }

    public function getServicePlanSiteCustomers($servicePlanId, $siteId)
    {
        return $this->connector->makeRequest('GET', "service-plans/{$servicePlanId}/sites/{$siteId}/customers");
    }

    public function getServicePlanSiteDevices($servicePlanId, $siteId)
    {
        return $this->connector->makeRequest('GET', "service-plans/{$servicePlanId}/sites/{$siteId}/devices");
    }

    public function getServicePlanSiteNetworks($servicePlanId, $siteId)
    {
        return $this->connector->makeRequest('GET', "service-plans/{$servicePlanId}/sites/{$siteId}/networks");
    }

    public function getNetworkSiteServicePlanCustomers($networkId, $siteId, $servicePlanId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/sites/{$siteId}/service-plans/{$servicePlanId}/customers");
    }

    public function getNetworkSiteServicePlanDevices($networkId, $siteId, $servicePlanId)
    {
        return $this->connector->makeRequest('GET', "networks/{$networkId}/sites/{$siteId}/service-plans/{$servicePlanId}/devices");
    }

    public function getServicePlanSiteNetworkCustomers($servicePlanId, $siteId, $networkId)
    {
        return $this->connector->makeRequest('GET', "service-plans/{$servicePlanId}/sites/{$siteId}/networks/{$networkId}/customers");
    }

    public function getServicePlanSiteNetworkDevices($servicePlanId, $siteId, $networkId)
    {
        return $this->connector->makeRequest('GET', "service-plans/{$servicePlanId}/sites/{$siteId}/networks/{$networkId}/devices");
    }

}
