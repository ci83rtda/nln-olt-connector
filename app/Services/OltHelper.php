<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OltHelper
{
    public static function parseOnuAutoFindOutput($output)
    {
        $lines = explode("\n", $output);
        $onus = [];

        foreach ($lines as $line) {
            // Remove escape characters for cursor movement
            $line = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $line);

            // Use regex to match the expected format and capture groups
            if (preg_match('/^(GPON\d+\/\d+:\d+)\s+(\S+)\s+(\S+)$/', trim($line), $matches)) {
                $onus[] = [
                    'OnuIndex' => trim($matches[1]),
                    'Sn' => trim($matches[2]),
                    'State' => trim($matches[3]),
                ];
            }
        }

        return $onus;
    }

    public static function parseExistingOnusOutput($output)
    {
        $lines = explode("\n", $output);
        $onus = [];

        foreach ($lines as $line) {
            // Remove escape characters for cursor movement
            $line = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $line);

            // Use regex to match the format and capture groups
            if (preg_match('/^GPON\d+\/\d+:(\d+)\s+\S+\s+\S+\s+\S+\s+(\S+)$/', trim($line), $matches)) {
                $onus[(int)$matches[1]] = [
                    'OnuId' => (int)$matches[1],
                    'Sn' => trim($matches[2]),
                ];
            }
        }

        return $onus;
    }

    public static function addOnu($oltConnector, $onuId, $serialNumber, $params)
    {
        // Determine the ONU type based on the serial number
        $onuType = self::determineOnuType($serialNumber);

        // Add the new ONU with the appropriate commands based on the ONU type
        if ($onuType === 'vsol') {
            self::addVsolOnu($oltConnector, $onuId, $serialNumber, $params);
        } elseif ($onuType === 'huawei') {
            self::addHuaweiOnu($oltConnector, $onuId, $serialNumber, $params);
        }
    }

    private static function determineOnuType($serialNumber)
    {
        if (strpos($serialNumber, 'GPON') === 0) {
            return 'vsol';
        } elseif (strpos($serialNumber, 'HWT') === 0) {
            return 'huawei';
        }
        return 'unknown';
    }

    private static function addVsolOnu($oltConnector, $onuId, $serialNumber, $params)
    {
        $oltConnector->executeCommand("onu add $onuId profile default sn $serialNumber", false);
        $oltConnector->executeCommand("onu $onuId desc {$params['description']}", false);
        $oltConnector->executeCommand("onu $onuId tcont 1 dba dba_INTERNET", false);
        $oltConnector->executeCommand("onu $onuId gemport 1 tcont 1 gemport_name gem_1", false);
        $oltConnector->executeCommand("onu $onuId gemport 1 traffic-limit upstream default downstream default", false);
        $oltConnector->executeCommand("onu $onuId service ser_1 gemport 1 vlan {$params['vlanid']}", false);
        $oltConnector->executeCommand("onu $onuId service-port 1 gemport 1 uservlan {$params['vlanid']} vlan {$params['vlanid']}", false);
        $oltConnector->executeCommand("onu $onuId portvlan veip 1 mode transparent", false);
        $oltConnector->executeCommand("onu $onuId pri equid {$params['model']}", false);
        $oltConnector->executeCommand("onu $onuId pri wan_adv add route", false);
        $oltConnector->executeCommand("onu $onuId pri wan_adv index 1 route mode internet mtu 1500", false);
        $oltConnector->executeCommand("onu $onuId pri wan_adv index 1 route ipv4 static ip {$params['ip']} mask {$params['mask']} gw {$params['gw']} dns master {$params['dns_master']} slave {$params['dns_slave']} nat enable", false);
        $oltConnector->executeCommand("onu $onuId pri wan_adv index 1 vlan tag wan_vlan {$params['vlanid']} 0", false);
        $oltConnector->executeCommand("onu $onuId pri wan_adv index 1 bind lan1 lan2 ssid1 ssid2 ssid3 ssid4 ssid5 ssid6 ssid7 ssid8", false);
        $oltConnector->executeCommand("onu $onuId pri wifi_switch 1 enable fcc auto 80211acANAC 20 40", false);
        $oltConnector->executeCommand("onu $onuId pri wifi_ssid 1 name {$params['wifi_ssid']} hide disable auth_mode wpa2psk encrypt_type tkipaes shared_key {$params['shared_key']} rekey_interval 0", false);
        $oltConnector->executeCommand("onu $onuId pri wifi_ssid 5 name {$params['wifi_ssid']} hide disable auth_mode wpa2psk encrypt_type tkipaes shared_key {$params['shared_key']} rekey_interval 0", false);
        $oltConnector->executeCommand("onu $onuId pri firewall level low", false);
        $oltConnector->executeCommand("onu $onuId pri acl ping control enable lan enable wan enable ipv4_control disable ipv6_control disable", false);
        $oltConnector->executeCommand("onu $onuId pri catv {$params['catv']}", false);
        $oltConnector->executeCommand('exit', false);
        $oltConnector->executeCommand('write memory', false);
    }

    private static function addHuaweiOnu($oltConnector, $onuId, $serialNumber, $params)
    {
        $oltConnector->executeCommand("onu add $onuId profile default sn $serialNumber", false);
        $oltConnector->executeCommand("onu $onuId desc {$params['description']}", false);
        $oltConnector->executeCommand("onu $onuId tcont 1 dba dba_INTERNET", false);
        $oltConnector->executeCommand("onu $onuId gemport 1 tcont 1 gemport_name gem_1", false);
        $oltConnector->executeCommand("onu $onuId gemport 1 traffic-limit upstream default downstream default", false);
        $oltConnector->executeCommand("onu $onuId service ser_1 gemport 1 vlan {$params['vlanid']}", false);
        $oltConnector->executeCommand("onu $onuId service-port 1 gemport 1 uservlan {$params['vlanid']} vlan {$params['vlanid']}", false);
        $oltConnector->executeCommand("onu $onuId portvlan veip 1 mode transparent", false);
        if (isset($params['video'])) {
            $oltConnector->executeCommand("onu $onuId video 1 state lock power {$params['video']}", false);
        }
        $oltConnector->executeCommand('exit', false);
        $oltConnector->executeCommand('write memory', false);
    }

    public static function changeWifiSettings($oltConnector, $port, $onuId, $wifiSettings)
    {
        // Enter the configuration context for the specific GPON port
        $oltConnector->executeCommand('configure terminal', false);
        $oltConnector->executeCommand("interface gpon 0/$port", false);

        foreach ($wifiSettings as $id => $settings) {
            // Toggle WiFi state if provided
            if ($settings['state'] !== null) {
                $state = $settings['state'] ? 'enable' : 'disable';
                $oltConnector->executeCommand("onu $onuId pri wifi_ssid $id $state fcc auto 80211acANAC 20 40", false);
            }

            // Update WiFi SSID if provided
            if ($settings['ssid'] !== null) {
                $oltConnector->executeCommand("onu $onuId pri wifi_ssid $id name {$settings['ssid']} hide disable auth_mode wpa2psk encrypt_type tkipaes shared_key {$settings['shared_key']} rekey_interval 0", false);
            } elseif ($settings['shared_key'] !== null) {
                // Update WiFi shared key if provided (assuming SSID remains the same)
                $currentSsid = self::parseWifiSsid($oltConnector->executeCommand("show onu $onuId pri wifi_ssid $id"));
                $oltConnector->executeCommand("onu $onuId pri wifi_ssid $id name $currentSsid hide disable auth_mode wpa2psk encrypt_type tkipaes shared_key {$settings['shared_key']} rekey_interval 0", false);
            }
        }

        // Exit configuration mode
        $oltConnector->executeCommand('!', false);
        $oltConnector->executeCommand('write memory', false);
    }

    public static function getCurrentWifiSettings($oltConnector, $port, $onuId)
    {
        // Enter the configuration context for the specific GPON port
        $oltConnector->executeCommand('configure terminal', false);
        $oltConnector->executeCommand("interface gpon 0/$port", false);

        // Get current WiFi settings for SSIDs 1-8
        $wifiSettings = [];
        for ($i = 1; $i <= 8; $i++) {
            $output = $oltConnector->executeCommand("show onu $onuId pri wifi_ssid $i");
            $wifiSettings[$i] = [
                'ssid' => self::parseWifiSsid($output),
                'shared_key' => self::parseSharedKey($output),
                'state' => self::parseWifiState($output),
            ];
        }

        // Exit configuration mode
        $oltConnector->executeCommand('!', false);
        $oltConnector->executeCommand('write memory', false);

        return $wifiSettings;
    }

    private static function parseWifiSsid($output)
    {
        preg_match('/name\s+([^\s]+)/', $output, $matches);
        return $matches[1] ?? null;
    }

    private static function parseSharedKey($output)
    {
        preg_match('/shared_key\s+([^\s]+)/', $output, $matches);
        return $matches[1] ?? null;
    }

    private static function parseWifiState($output)
    {
        preg_match('/hide\s+(enable|disable)/', $output, $matches);
        return $matches[1] === 'enable';
    }

}
