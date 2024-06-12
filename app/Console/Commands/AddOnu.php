<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
    public function handle(OltConnector $oltConnector)
    {
        $port = $this->askWithValidation('Enter the GPON port number (e.g., 5)');
        $serialNumber = $this->askWithValidation('Enter the ONU serial number (e.g., GPON009777F0)');
        $onuType = $this->determineOnuType($serialNumber);

        $params = [];
        $params['description'] = $this->askWithValidation('Enter the description');
        $params['vlanid'] = $this->askWithValidation('Enter the VLAN ID');

        if ($onuType === 'vsol') {
            $params['model'] = $this->askWithValidation('Enter the model number');
            $params['ip'] = $this->askWithValidation('Enter the static IP address');
            $params['mask'] = $this->askWithValidation('Enter the subnet mask');
            $params['gw'] = $this->askWithValidation('Enter the gateway IP address');
            $params['dns_master'] = $this->askWithValidation('Enter the primary DNS server');
            $params['dns_slave'] = $this->askWithValidation('Enter the secondary DNS server');
            $params['wifi_ssid'] = $this->askWithValidation('Enter the WiFi SSID');
            $params['shared_key'] = $this->askWithValidation('Enter the WiFi shared key');
            $params['catv'] = $this->choice('Enable CATV?', ['enable', 'disable'], 'disable');
        } elseif ($onuType === 'huawei') {
            $params['video'] = $this->choice('Enable video? unlock: enable, lock: disable', ['unlock', 'lock'], 'lock');
        } else {
            $this->error('Unknown ONU type.');
            return 1;
        }

        try {
            Log::info('Attempting to connect to OLT');
            $oltConnector->addOnu($port, $serialNumber, $params);
            Log::info('Connected to OLT, adding ONU');
            $this->info('ONU added successfully.');
        } catch (\Exception $e) {
            Log::error('Error adding ONU: ' . $e->getMessage());
            $this->error('Failed to add ONU. Check logs for details.');
        }

        return 0;
    }

    private function askWithValidation($question)
    {
        do {
            $response = $this->ask($question);
            if (empty($response)) {
                $this->error('This field is required.');
            }
        } while (empty($response));

        return $response;
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
