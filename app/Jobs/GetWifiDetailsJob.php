<?php

namespace App\Jobs;

use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class GetWifiDetailsJob extends BaseTaskJob
{
    /**
     * Execute the job.
     *
     * @param OltConnector $oltConnector
     * @return void
     */
    public function handle(OltConnector $oltConnector)
    {
        $task = $this->task;
        try {
            $port = $task['port'];
            $onuId = $task['onuId'];
            $asJson = true;

            $wifiDetails = $oltConnector->getWifiDetails($port, $onuId, $asJson);

            Log::info("WiFi details retrieved for ONU $onuId on port $port.");
            $this->reportCompletion('success', $wifiDetails);
        } catch (\Exception $e) {
            Log::error('Error retrieving WiFi details: ' . $e->getMessage());
            $this->reportCompletion('error', $e->getMessage());
        }

        // Ensure the job stops after handling the task
        return;
    }
}
