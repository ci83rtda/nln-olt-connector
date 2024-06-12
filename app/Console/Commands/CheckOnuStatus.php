<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOnuStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-onu-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of an ONU';

    /**
     * Execute the console command.
     */
    public function handle(OltConnector $oltConnector)
    {
        $port = $this->askWithValidation('Enter the GPON port number (e.g., 5)');
        $onuId = $this->askWithValidation('Enter the ONU ID (e.g., 28)');
        $asJson = $this->choice('Return details as JSON?', ['false', 'true'], 'false') === 'true';

        try {
            Log::info('Attempting to retrieve status for ONU');
            $status = $oltConnector->getOnuStatus($port, $onuId, $asJson);
            Log::info('ONU status retrieved successfully.');
            $this->info('ONU status retrieved successfully.');

            if ($asJson) {
                $this->line($status);
            } else {
                if (is_string($status)) {
                    $this->info($status);
                } else {
                    $this->displayOnuStatus($status);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error retrieving ONU status: ' . $e->getMessage());
            $this->error('Failed to retrieve ONU status. Check logs for details.');
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

    private function displayOnuStatus($status)
    {
        $this->info('Optical Information:');
        $this->info("Rx Optical Level: {$status['optical_info']['rx_optical_level']} dBm");
        $this->info("Tx Optical Level: {$status['optical_info']['tx_optical_level']} dBm");
        $this->info("Temperature: {$status['optical_info']['temperature']} C");

        $this->info('Distance Information:');
        $this->info("Distance: {$status['distance']} meters");
    }
}
