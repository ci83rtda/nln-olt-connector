<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class AddEditWifiSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-edit-wifi-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add or edit WiFi settings for Vsol ONUs';

    /**
     * Execute the console command.
     */
    public function handle(OltConnector $oltConnector)
    {
        $port = $this->askWithValidation('Enter the GPON port number (e.g., 5)');
        $onuId = $this->askWithValidation('Enter the ONU ID (e.g., 28)');
        $model = $this->choice('Select the ONU model', ['V452', 'V642']);

        // Fetch WiFi switch settings
        $wifiSwitchSettings = [];
        if ($model === 'V452') {
            $wifiSwitchSettings[1] = $this->choice('WiFi switch 1 (2.4 GHz): enable or disable?', ['enable', 'disable'], 'enable');
            $wifiSwitchSettings[2] = $this->choice('WiFi switch 2 (5 GHz): enable or disable?', ['enable', 'disable'], 'enable');
        } else {
            $wifiSwitchSettings[1] = $this->choice('WiFi switch (2.4 GHz): enable or disable?', ['enable', 'disable'], 'enable');
        }

        // Fetch WiFi SSID settings
        $ssidRange = $model === 'V642' ? range(1, 4) : range(1, 8);
        $wifiSettings = [];
        foreach ($ssidRange as $i) {
            $frequency = ($i <= 4) ? '2.4 GHz' : '5 GHz';
            $wifiSettings[$i] = [
                'state' => $this->choice("WiFi SSID $i ($frequency): enable or disable?", ['enable', 'disable'], 'enable')
            ];

            if ($wifiSettings[$i]['state'] === 'enable') {
                $wifiSettings[$i]['ssid'] = $this->ask("Enter the WiFi SSID for SSID $i ($frequency)");
                $wifiSettings[$i]['shared_key'] = $this->ask("Enter the WiFi shared key for SSID $i ($frequency)");
            } else {
                $wifiSettings[$i]['ssid'] = 'NewLine-WiFi';
            }
        }

        try {
            Log::info('Attempting to add/edit WiFi settings for ONU');
            $oltConnector->changeWifiSettings($port, $onuId, $wifiSettings, $wifiSwitchSettings, $model);
            Log::info('WiFi settings added/edited successfully.');
            $this->info('WiFi settings added/edited successfully.');
        } catch (\Exception $e) {
            Log::error('Error adding/editing WiFi settings: ' . $e->getMessage());
            $this->error('Failed to add/edit WiFi settings. Check logs for details.');
        }

        // Exit configuration mode
        $oltConnector->executeCommand('exit', false);
        $oltConnector->executeCommand('write memory', false);

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
}
