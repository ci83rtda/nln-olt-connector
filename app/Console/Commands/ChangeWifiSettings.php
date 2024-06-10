<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ChangeWifiSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-wifi-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change WiFi name and password for Vsol ONUs';

    /**
     * Execute the console command.
     */
    public function handle(OltConnector $oltConnector)
    {
        $port = $this->askWithValidation('Enter the GPON port number (e.g., 5)');
        $onuId = $this->askWithValidation('Enter the ONU ID (e.g., 28)');

        // Determine the ONU model
        $equid = $oltConnector->executeCommand("onu $onuId pri equid");
        $model = $this->parseModel($equid);

        // Fetch current WiFi settings
        $currentSettings = $oltConnector->getCurrentWifiSettings($port, $onuId);

        // Determine the range of SSIDs to configure based on the model
        $ssidRange = $model === 'VSOLV642' ? range(1, 4) : range(1, 8);

        $wifiSettings = [];
        foreach ($ssidRange as $i) {
            $currentState = $currentSettings[$i]['state'];
            $wifiSettings[$i] = [
                'state' => $this->choice("Enable, disable, or no change for WiFi SSID $i?", ['enable', 'disable', 'no change'], 'no change')
            ];

            if ($wifiSettings[$i]['state'] === 'enable') {
                $wifiSettings[$i]['ssid'] = $this->ask("Enter the new WiFi SSID for SSID $i", $currentSettings[$i]['ssid']);
                $wifiSettings[$i]['shared_key'] = $this->ask("Enter the new WiFi shared key for SSID $i", $currentSettings[$i]['shared_key']);
            } elseif ($wifiSettings[$i]['state'] === 'no change' && $currentState !== false) {
                $wifiSettings[$i]['ssid'] = $this->ask("Enter the WiFi SSID for SSID $i", $currentSettings[$i]['ssid']);
                $wifiSettings[$i]['shared_key'] = $this->ask("Enter the WiFi shared key for SSID $i", $currentSettings[$i]['shared_key']);
            } else {
                $wifiSettings[$i]['ssid'] = null;
                $wifiSettings[$i]['shared_key'] = null;
            }
        }

        $wifiSwitchSettings = [];
        if ($model === 'VSOLV452') {
            $wifiSwitchSettings = [
                1 => $this->choice("Enable, disable, or no change for 2.4 GHz WiFi switch?", ['enable', 'disable', 'no change'], 'no change'),
                2 => $this->choice("Enable, disable, or no change for 5.0 GHz WiFi switch?", ['enable', 'disable', 'no change'], 'no change')
            ];
        } else {
            $wifiSwitchSettings = [
                1 => $this->choice("Enable, disable, or no change for 2.4 GHz WiFi switch?", ['enable', 'disable', 'no change'], 'no change')
            ];
        }

        try {
            Log::info('Attempting to change WiFi settings for ONU');
            $oltConnector->changeWifiSettings($port, $onuId, $wifiSettings, $wifiSwitchSettings, $model);
            Log::info('WiFi settings changed successfully.');
            $this->info('WiFi settings changed successfully.');
        } catch (\Exception $e) {
            Log::error('Error changing WiFi settings: ' . $e->getMessage());
            $this->error('Failed to change WiFi settings. Check logs for details.');
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

    private function parseModel($equidOutput)
    {
        preg_match('/equid\s+([^\s]+)/', $equidOutput, $matches);
        return $matches[1] ?? 'unknown';
    }
}
