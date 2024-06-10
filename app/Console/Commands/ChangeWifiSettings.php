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

        // Fetch current WiFi settings
        $currentSettings = $oltConnector->getCurrentWifiSettings($port, $onuId);

        $wifiSettings = [];
        for ($i = 1; $i <= 8; $i++) {
            $currentState = $currentSettings[$i]['state'];
            if ($currentState === false) {
                $enableWifi = $this->confirm("WiFi SSID $i is currently disabled. Do you want to enable it?", false);
                if ($enableWifi) {
                    $wifiSettings[$i] = [
                        'ssid' => $this->ask("Enter the new WiFi SSID for SSID $i"),
                        'shared_key' => $this->ask("Enter the new WiFi shared key for SSID $i"),
                        'state' => true,
                    ];
                } else {
                    $wifiSettings[$i] = [
                        'ssid' => null,
                        'shared_key' => null,
                        'state' => null,
                    ];
                }
            } else {
                $stateChoice = $this->choice("Enable, disable, or no change for WiFi SSID $i?", ['enable', 'disable', 'no change'], 'no change');
                if ($stateChoice === 'disable') {
                    $wifiSettings[$i] = [
                        'ssid' => null,
                        'shared_key' => null,
                        'state' => false,
                    ];
                } elseif ($stateChoice === 'enable') {
                    $wifiSettings[$i] = [
                        'ssid' => $this->ask("Enter the new WiFi SSID for SSID $i", $currentSettings[$i]['ssid']),
                        'shared_key' => $this->ask("Enter the new WiFi shared key for SSID $i", $currentSettings[$i]['shared_key']),
                        'state' => true,
                    ];
                } else {
                    $wifiSettings[$i] = [
                        'ssid' => null,
                        'shared_key' => null,
                        'state' => null,
                    ];
                }
            }
        }

        try {
            Log::info('Attempting to change WiFi settings for ONU');
            $oltConnector->changeWifiSettings($port, $onuId, $wifiSettings);
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
}
