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
        $this->ssh->read();
        $this->ssh->write("$password\n");
        $loginOutput = $this->ssh->read('/#\s/');

        if (strpos($loginOutput, 'User Access Verification') !== false) {
            throw new \Exception('User Access Verification login failed');
        }
    }

    public function executeCommand($command, $interactive = false)
    {
        Log::info("Executing command: $command");
        if ($interactive) {
            $this->ssh->write("$command\n");
            $output = $this->ssh->read('/#\s/');
        } else {
            $output = $this->ssh->exec($command);
        }
        Log::info("Command output: $output");
        return $output;
    }

    public function fetchPendingOnus()
    {
        $this->executeCommand('enable', true);
        $this->ssh->write($this->enablePassword . "\n");
        $this->ssh->read('/#\s/');
        $this->executeCommand('configure terminal', true);

        $pendingOnus = [];

        for ($port = 1; $port <= 8; $port++) {
            $this->executeCommand("interface gpon 0/$port", true);
            $output = $this->executeCommand('show onu auto-find', true);

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
