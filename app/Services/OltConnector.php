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

}
