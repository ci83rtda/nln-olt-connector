<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use Illuminate\Console\Command;

class AddOnu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-onu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add an ONU to the OLT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $port = $this->ask('Enter the GPON port number (e.g., 5)');
        $serialNumber = $this->ask('Enter the ONU serial number (e.g., GPON009777F0)');
        $onuType = $this->determineOnuType($serialNumber);

        $params = [];
        $params['description'] = $this->ask('Enter the description');
        $params['vlanid'] = $this->ask('Enter the VLAN ID');

        if ($onuType === 'vsol') {
            $params['model'] = $this->ask('Enter the model number');
            $params['ip'] = $this->ask('Enter the static IP address');
            $params['mask'] = $this->ask('Enter the subnet mask');
            $params['gw'] = $this->ask('Enter the gateway IP address');
            $params['dns_master'] = $this->ask('Enter the primary DNS server');
            $params['dns_slave'] = $this->ask('Enter the secondary DNS server');
            $params['wifi_ssid_1'] = $this->ask('Enter the first WiFi SSID');
            $params['shared_key_1'] = $this->ask('Enter the first WiFi shared key');
            $params['wifi_ssid_2'] = $this->ask('Enter the second WiFi SSID');
            $params['shared_key_2'] = $this->ask('Enter the second WiFi shared key');
            $params['catv'] = $this->choice('Enable CATV?', ['enable', 'disable'], 'disable');
        } elseif ($onuType === 'huawei') {
            $params['video'] = $this->choice('Enable video?', ['enable', 'disable'], 'disable');
        } else {
            $this->error('Unknown ONU type.');
            return 1;
        }

        $oltConnector = new OltConnector('olt_host', 'username', 'password', 'enable_password');
        $oltConnector->addOnu($port, $serialNumber, $params);

        $this->info('ONU added successfully.');
        return 0;
    }

    private function determineOnuType($serialNumber)
    {
        if (strpos($serialNumber, 'GPON') === 0) {
            return 'vsol';
        } elseif (strpos($serialNumber, 'HWT') === 0) {
            return 'huawei';
        }
        return 'unknown';
    }
}
