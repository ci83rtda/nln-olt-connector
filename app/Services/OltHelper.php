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
            // Remove ANSI escape sequences and control characters
            $line = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $line);
            $line = preg_replace('/[\r\n\x0b\x0c\e]/', '', $line);

            // Use regex to match the ONU ID
            if (preg_match('/GPON\d+\/\d+:(\d+)/', trim($line), $matches)) {
                $onus[(int)$matches[1]] = (int)$matches[1];
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
            $oltConnector->executeCommand("onu $onuId video 1 state {$params['video']} power disable", false);
        }
        $oltConnector->executeCommand('exit', false);
        $oltConnector->executeCommand('write memory', false);
    }

    public static function getCurrentWifiSettings($oltConnector, $port, $onuId, $model)
    {
        // Get current WiFi switch settings
        $wifiSwitchSettings = [];
        if ($model === 'V452') {
            $output = $oltConnector->executeCommand("show onu $onuId pri wifi_switch");
            $wifiSwitchSettings = self::parseV452WifiSwitchState($output);
        } else { // For V642
            $output = $oltConnector->executeCommand("show onu $onuId pri wifi_switch");
            $wifiSwitchSettings[1] = self::parseWifiState($output);
        }

        // Get current WiFi settings for SSIDs
        $ssidRange = $model === 'V642' ? range(1, 4) : range(1, 8);
        $wifiSettings = [];
        foreach ($ssidRange as $i) {
            $output = $oltConnector->executeCommand("show onu $onuId pri wifi_ssid $i");
            $wifiSettings[$i] = [
                'ssid' => self::parseWifiSsid($output),
                'shared_key' => self::parseSharedKey($output),
                'state' => self::parseWifiState($output),
            ];
        }

        return [
            'wifi_switch' => $wifiSwitchSettings,
            'ssid' => $wifiSettings
        ];
    }

    private static function parseV452WifiSwitchState($output)
    {
        $wifiSwitchSettings = [];
        preg_match_all('/Index\s+:\s+Wifi\d+\s+Status\s+:\s+(Enable|Disable)/', $output, $matches, PREG_SET_ORDER);
        foreach ($matches as $index => $match) {
            $wifiSwitchSettings[$index + 1] = ($match[1] === 'Enable') ? 'enable' : 'disable';
        }
        return $wifiSwitchSettings;
    }

    private static function parseWifiSsid($output)
    {
        preg_match('/Name\s+:\s+([^\s]+)/', $output, $matches);
        return $matches[1] ?? null;
    }

    private static function parseSharedKey($output)
    {
        preg_match('/Preshared Key\s+:\s+([^\s]+)/', $output, $matches);
        return $matches[1] ?? null;
    }

    private static function parseWifiState($output)
    {
        preg_match('/Status\s+:\s+(Enable|Disable)/', $output, $matches);
        return isset($matches[1]) ? ($matches[1] === 'Enable' ? 'enable' : 'disable') : null;
    }

    public static function changeWifiSettings($oltConnector, $port, $onuId, $wifiSettings, $wifiSwitchSettings, $model)
    {
        foreach ($wifiSwitchSettings as $switch => $state) {
            if ($state !== 'no change') {
                if ($state === 'enable') {
                    if ($model === 'V452') {
                        $command = ($switch === 1) ?
                            "onu $onuId pri wifi_switch 1 enable fcc auto 80211ac0 20 40" :
                            "onu $onuId pri wifi_switch 2 enable fcc channel 0 80211bgn 20";
                    } else { // For V642
                        $command = "onu $onuId pri wifi_switch 1 enable fcc channel 0 80211bgn 20 20/40";
                    }
                } elseif ($state === 'disable') {
                    $command = "onu $onuId pri wifi_switch $switch disable";
                }
                $oltConnector->executeCommand($command, false);
            }
        }

        foreach ($wifiSettings as $id => $settings) {
            // Update WiFi SSID if provided
            if ($settings['state'] === 'enable' || ($settings['state'] === 'no change' && $settings['ssid'] !== null)) {
                $oltConnector->executeCommand("onu $onuId pri wifi_ssid $id name {$settings['ssid']} hide disable auth_mode wpa2psk encrypt_type tkipaes shared_key {$settings['shared_key']} rekey_interval 0", false);
            } elseif ($settings['state'] === 'disable') {
                $oltConnector->executeCommand("onu $onuId pri wifi_ssid $id disable name NewLine-WiFi", false);
            }
        }
    }

    public static function parseWifiSwitchDetails($output)
    {
        $details = [];
        preg_match_all('/Index\s+:\s+Wifi\d+\s+Status\s+:\s+(Enable|Disable)/', $output, $matches, PREG_SET_ORDER);
        foreach ($matches as $index => $match) {
            $details[$index + 1] = ($match[1] === 'Enable') ? 'enable' : 'disable';
        }
        return $details;
    }

    public static function parseWifiSsidDetails($output)
    {
        preg_match('/Name\s+:\s+([^\s]+)/', $output, $nameMatches);
        preg_match('/Preshared Key\s+:\s+([^\s]+)/', $output, $keyMatches);
        preg_match('/Status\s+:\s+(Enable|Disable)/', $output, $stateMatches);

        return [
            'ssid' => $nameMatches[1] ?? null,
            'shared_key' => $keyMatches[1] ?? null,
            'state' => isset($stateMatches[1]) ? ($stateMatches[1] === 'Enable' ? 'enable' : 'disable') : null,
        ];
    }

    public static function parseWifiStatus($output)
    {
        preg_match('/Status\s+:\s+(Enable|Disable)/', $output, $matches);
        return isset($matches[1]) ? ($matches[1] === 'Enable' ? 'enable' : 'disable') : null;
    }

    public static function parseOpticalInfo($output)
    {
        // Remove ANSI escape sequences
        $output = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $output);

        $info = [];
        preg_match('/Rx optical level:\s+([-\d.]+|N\/A)\(dBm\)/', $output, $rxMatches);
        preg_match('/Tx optical level:\s+([-\d.]+|N\/A)\(dBm\)/', $output, $txMatches);
        preg_match('/Temperature:\s+([-\d.]+|N\/A)\(C\)/', $output, $tempMatches);

        $info['rx_optical_level'] = $rxMatches[1] ?? 'N/A';
        $info['tx_optical_level'] = $txMatches[1] ?? 'N/A';
        $info['temperature'] = $tempMatches[1] ?? 'N/A';

        return $info;
    }

    public static function parseDistance($output)
    {
        // Remove ANSI escape sequences
        $output = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $output);

        preg_match('/Distance:\s+(\d+)/', $output, $matches);
        return $matches[1] ?? null;
    }


    public static function parseAutoFindOnusOutput($output)
    {
        $lines = explode("\n", $output);
        $onus = [];

        foreach ($lines as $line) {
            // Remove ANSI escape sequences and control characters
            $line = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $line);
            $line = preg_replace('/[\r\n\x0b\x0c\e]/', '', $line);

            // Use regex to match the ONU ID and Serial Number
            if (preg_match('/GPON\d+\/\d+:\s+(\S+)/', trim($line), $matches)) {
                $onus[] = [
                    'Sn' => $matches[1]
                ];
            }
        }

        return $onus;
    }

}
