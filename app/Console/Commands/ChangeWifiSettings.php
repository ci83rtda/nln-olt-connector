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

        // Enable and enter the configuration context for the specific GPON port
        $oltConnector->enable();
        $oltConnector->executeCommand('configure terminal', false);
        $oltConnector->executeCommand("interface gpon 0/$port", false);

        // Determine the ONU model
        $equid = $oltConnector->executeCommand("onu $onuId pri equid");
        $model = $this->parseModel($equid);

        // Fetch current WiFi settings
        $currentSettings = $oltConnector->getCurrentWifiSettings($port, $onuId, $model);

        // Handle WiFi switch status
        $wifiSwitchSettings = [];
        $bands = $model === 'VSOLV452' ? [1, 2] : [1];
        foreach ($bands as $band) {
            $currentState = $currentSettings["wifi_switch"][$band] ?? 'unknown';
            $options = ($currentState === 'enable') ? ['disable', 'no change'] : ['enable', 'no change'];
            $wifiSwitchSettings[$band] = $this->choice("Current status for WiFi switch $band is $currentState. Enable, disable, or no change?", $options, 'no change');
        }

        // Handle SSID settings
        $ssidRange = $model === 'VSOLV642' ? range(1, 4) : range(1, 8);
        $wifiSettings = [];
        foreach ($ssidRange as $i) {
            $currentState = $currentSettings["ssid"][$i]['state'] ?? 'unknown';
            $options = ($currentState === 'enable') ? ['disable', 'no change'] : ['enable', 'no change'];
            $wifiSettings[$i] = [
                'state' => $this->choice("Current status for WiFi SSID $i is $currentState. Enable, disable, or no change?", $options, 'no change')
            ];

            if ($wifiSettings[$i]['state'] === 'enable') {
                $wifiSettings[$i]['ssid'] = $this->ask("Enter the new WiFi SSID for SSID $i", $currentSettings["ssid"][$i]['ssid'] ?? '');
                $wifiSettings[$i]['shared_key'] = $this->ask("Enter the new WiFi shared key for SSID $i", $currentSettings["ssid"][$i]['shared_key'] ?? '');
            } elseif ($wifiSettings[$i]['state'] === 'no change') {
                $wifiSettings[$i]['ssid'] = $currentSettings["ssid"][$i]['ssid'] ?? '';
                $wifiSettings[$i]['shared_key'] = $currentSettings["ssid"][$i]['shared_key'] ?? '';
            } else {
                $wifiSettings[$i]['ssid'] = null;
                $wifiSettings[$i]['shared_key'] = null;
            }
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

    private function parseModel($equidOutput)
    {
        preg_match('/equid\s+([^\s]+)/', $equidOutput, $matches);
        return $matches[1] ?? 'unknown';
    }
}
