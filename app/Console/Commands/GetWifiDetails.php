<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GetWifiDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-wifi-details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get WiFi details for a given ONU';

    /**
     * Execute the console command.
     */
    public function handle(OltConnector $oltConnector)
    {
        $port = $this->askWithValidation('Enter the GPON port number (e.g., 5)');
        $onuId = $this->askWithValidation('Enter the ONU ID (e.g., 28)');
        $asJson = $this->choice('Return details as JSON?', ['false', 'true'], 'false') === 'true';

        try {
            Log::info('Attempting to retrieve WiFi details for ONU');
            $details = $oltConnector->getWifiDetails($port, $onuId, $asJson);
            Log::info('WiFi details retrieved successfully.');
            $this->info('WiFi details retrieved successfully.');

            if ($asJson) {
                $this->line($details);
            } else {
                if (is_string($details)) {
                    $this->info($details);
                } else {
                    $this->displayWifiDetails($details);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error retrieving WiFi details: ' . $e->getMessage());
            $this->error('Failed to retrieve WiFi details. Check logs for details.');
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

    private function displayWifiDetails($details)
    {
        $this->info('WiFi Switch Details:');
        foreach ($details['wifi_switch'] as $index => $state) {
            $frequency = ($index == 1) ? '2.4 GHz' : '5.0 GHz';
            $this->info("Switch $index ($frequency): $state");
        }

        $this->info('WiFi SSID Details:');
        foreach ($details['ssid'] as $index => $ssidDetails) {
            $frequency = ($index <= 4) ? '2.4 GHz' : '5.0 GHz';
            $this->info("SSID $index ($frequency): Name: {$ssidDetails['ssid']}, Status: {$ssidDetails['state']}, Shared Key: {$ssidDetails['shared_key']}");
        }
    }
}
