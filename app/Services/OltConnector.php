<?php

namespace App\Services;

use App\Traits\SshConnectionTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class OltConnector
{

    use SshConnectionTrait;

    protected $ssh;
    protected $enablePassword;

    public function __construct($host, $username, $password, $enablePassword)
    {
        $this->ssh = new SSH2($host);
        $this->enablePassword = $enablePassword;

        $this->ssh->setTimeout(10);

        // Initial login
        if (!$this->ssh->login($username, $password)) {
            throw new \Exception('Initial login failed');
        }

        // User Access Verification
        $this->ssh->write("$username\n");
        $this->ssh->read('/Password:\s*/');
        $this->ssh->write("$password\n");
        $this->ssh->read('/#\s*/');
    }

    public function executeCommand($command, $expectOutput = true)
    {
        Log::info("Executing command: $command");
        $this->ssh->write("$command\n");
        $output = '';

        if ($expectOutput) {
            while (true) {
                $response = $this->ssh->read('/#\s*/', SSH2::READ_REGEX);
                $output .= $response;

                if (strpos($response, '--More--') !== false) {
                    $this->ssh->write(" "); // Send space to continue the output
                } else {
                    break;
                }
            }
            Log::info("Command response: $output");
            return $output;
        } else {
            // Wait until the prompt returns
            while (true) {
                $response = $this->ssh->read('/#\s*/', SSH2::READ_REGEX);
                $output .= $response;

                if (strpos($response, '--More--') !== false) {
                    $this->ssh->write(" "); // Send space to continue the output
                } else {
                    break;
                }
            }
            Log::info("Command response: $output");
            return '';
        }
    }

    public function fetchPendingOnus()
    {
        $this->executeCommand('enable', false);
        $this->ssh->write($this->enablePassword . "\n");
        $this->ssh->read('/#\s*/', SSH2::READ_REGEX);
        $this->executeCommand('configure terminal', false);

        $pendingOnus = [];

        for ($port = 1; $port <= 8; $port++) {
            $this->executeCommand("interface gpon 0/$port", false);
            $output = $this->executeCommand('show onu auto-find');

            if (strpos($output, 'No related information to show') === false) {
                $onus = OltHelper::parseOnuAutoFindOutput($output);

                foreach ($onus as &$onu) {
                    $onu['Port'] = "0/$port";
                }

                $pendingOnus = array_merge($pendingOnus, $onus);
            }
        }

        Cache::put('pending_onus', $pendingOnus);

        $this->closeConnection();

        return $pendingOnus;
    }

    public function addOnu($port, $serialNumber, $params)
    {
        $this->executeCommand('enable', false);
        $this->ssh->write($this->enablePassword . "\n");
        $this->ssh->read('/#\s*/', SSH2::READ_REGEX);
        $this->executeCommand('configure terminal', false);
        $this->executeCommand("interface gpon 0/$port", false);

        // Fetch existing ONUs to find an available ID
        $existingOnusOutput = $this->executeCommand('show onu info');
        $existingOnus = OltHelper::parseExistingOnusOutput($existingOnusOutput);

        // Find the first available ONU ID
        $availableOnuId = 1;
        while (array_key_exists($availableOnuId, $existingOnus)) {
            $availableOnuId++;
        }

        // Delegate the ONU addition to the helper class
        OltHelper::addOnu($this, $availableOnuId, $serialNumber, $params);

        $this->closeConnection();
    }

    public function enable()
    {
        $this->executeCommand('enable', false);
        $this->ssh->write($this->enablePassword . "\n");
        $this->ssh->read('/#\s*/', SSH2::READ_REGEX);
    }

    public function getCurrentWifiSettings($port, $onuId, $model)
    {
        return OltHelper::getCurrentWifiSettings($this, $port, $onuId, $model);
    }

    public function changeWifiSettings($port, $onuId, $wifiSettings, $wifiSwitchSettings, $model)
    {
        OltHelper::changeWifiSettings($this, $port, $onuId, $wifiSettings, $wifiSwitchSettings, $model);
    }

    public function toggleCatvStatus($port, $onuId, $model, $newStatus)
    {
        $command = '';

        switch ($model) {
            case 'V452':
            case 'V642':
                $command = "onu $onuId pri catv $newStatus";
                break;
            case 'EG8143H5':
                $command = "onu $onuId video 1 state lock power $newStatus";
                break;
            default:
                throw new \Exception('Unsupported ONU model.');
        }

        // Enable, configure terminal, and interface gpon
        $this->enable();
        $this->executeCommand('configure terminal', false);
        $this->executeCommand("interface gpon 0/$port", false);

        // Execute the command to set CATV status
        $this->executeCommand($command, false);

        // Exit configuration mode and save configuration
        $this->executeCommand('exit', false);
        $this->executeCommand('write memory', false);

        Log::info("Set CATV status for ONU $onuId on port $port to $newStatus.");
    }


    public function getWifiDetails($port, $onuId, $asJson = false)
    {
        $details = [];

        // Enable, configure terminal, and interface gpon
        $this->enable();
        $this->executeCommand('configure terminal', false);
        $this->executeCommand("interface gpon 0/$port", false);

        // Get WiFi switch details
        $switchOutput = $this->executeCommand("show onu $onuId pri wifi_switch", true);
        $details['wifi_switch'] = OltHelper::parseWifiSwitchDetails($switchOutput);

        // Determine the number of SSIDs based on switch details
        $ssidRange = isset($details['wifi_switch'][2]) ? range(1, 8) : range(1, 4);

        // Get WiFi SSID details
        foreach ($ssidRange as $i) {
            $ssidOutput = $this->executeCommand("show onu $onuId pri wifi_ssid $i", true);
            if (empty($ssidOutput)) {
                continue;
            }
            $ssidDetails = OltHelper::parseWifiSsidDetails($ssidOutput);
            if (!empty($ssidDetails['ssid'])) {
                $details['ssid'][$i] = $ssidDetails;
            }
        }

        // Exit configuration mode
        $this->executeCommand('exit', false);

        Log::info("Retrieved WiFi details for ONU $onuId on port $port.");

        if (empty($details['wifi_switch']) && empty($details['ssid'])) {
            return $asJson ? json_encode(['error' => 'No data found or device not compatible.']) : 'No data found or device not compatible.';
        }

        if ($asJson) {
            return json_encode($details);
        }

        return $details;
    }

    public function getOnuStatus($port, $onuId, $asJson = false)
    {
        $status = [
            'optical_info' => [],
            'distance' => null
        ];

        // Enable, configure terminal, and interface gpon
        $this->enable();
        $this->executeCommand('configure terminal', false);
        $this->executeCommand("interface gpon 0/$port", false);

        // Get optical information
        $opticalOutput = $this->executeCommand("show onu $onuId optical_info", true);
        $parsedOpticalInfo = OltHelper::parseOpticalInfo($opticalOutput);
        $status['optical_info'] = $parsedOpticalInfo;

        // Get distance information
        $distanceOutput = $this->executeCommand("show onu $onuId distance", true);
        $parsedDistance = OltHelper::parseDistance($distanceOutput);
        $status['distance'] = $parsedDistance;

        // Exit configuration mode
        $this->executeCommand('exit', false);

        Log::info("Retrieved status for ONU $onuId on port $port.");

        if (empty($status['optical_info']) && $status['distance'] === null) {
            return $asJson ? json_encode(['error' => 'No data found or device not compatible.']) : 'No data found or device not compatible.';
        }

        if ($asJson) {
            return json_encode($status);
        }

        return $status;
    }

}
