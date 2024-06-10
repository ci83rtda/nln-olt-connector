<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class OltConnector
{

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
        $loginOutput = $this->ssh->read('/#\s*/');

        if (strpos($loginOutput, 'User Access Verification') !== false) {
            throw new \Exception('User Access Verification login failed');
        }
    }

    public function executeCommand($command, $expectOutput = true)
    {
        Log::info("Executing command: $command");
        $this->ssh->write("$command\n");
        if ($expectOutput) {
            $output = $this->ssh->read('/#\s*/');
            Log::info("Command output: $output");
            return $output;
        } else {
            // Adding a small delay to ensure the command is processed
            sleep(1);
            return '';
        }
    }

    public function fetchPendingOnus()
    {
        $this->executeCommand('enable', false);
        $this->ssh->write($this->enablePassword . "\n");
        $this->ssh->read('/#\s*/');
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

    public function closeConnection()
    {
        $this->ssh->disconnect();
    }

}
