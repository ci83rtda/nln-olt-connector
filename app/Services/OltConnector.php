<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use phpseclib3\Net\SSH2;

class OltConnector
{

    protected $ssh;
    protected $enablePassword;

    public function __construct($host, $username, $password, $enablePassword)
    {
        $this->ssh = new SSH2($host);
        if (!$this->ssh->login($username, $password)) {
            throw new \Exception('Login failed');
        }
        $this->enablePassword = $enablePassword;
    }

    public function executeCommand($command)
    {
        return $this->ssh->exec($command);
    }

    public function fetchPendingOnus()
    {
        $this->executeCommand('enable');
        $this->ssh->write($this->enablePassword . "\n");
        $this->executeCommand('configure terminal');

        $pendingOnus = [];
        $data = '';

        for ($port = 1; $port <= 8; $port++) {
            $this->executeCommand("interface gpon 0/$port");
            $output = $this->executeCommand('show onu auto-find');
            $data .= $output;

            $onus = OltHelper::parseOnuAutoFindOutput($output);

            foreach ($onus as &$onu) {
                $onu['Port'] = "0/$port";
            }

            $pendingOnus = array_merge($pendingOnus, $onus);
        }

        dd($data);

        Cache::put('pending_onus', $pendingOnus);

        $this->closeConnection();

        return $pendingOnus;
    }

    public function closeConnection()
    {
        $this->ssh->disconnect();
    }

}
