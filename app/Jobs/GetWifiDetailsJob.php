<?php

namespace App\Jobs;

use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class GetWifiDetailsJob extends BaseTaskJob
{
    public function handle(OltConnector $oltConnector)
    {
        $task = $this->task;
        try {
            $port = $task['port'];
            $onuId = $task['onuId'];
            $asJson = true;

            Log::info("Starting GetWifiDetailsJob for task: " . $task['request_id']);

            $wifiDetails = $oltConnector->getWifiDetails($port, $onuId, $asJson);

            Log::info("WiFi details retrieved for ONU $onuId on port $port.");
            Log::info("WiFi details: $wifiDetails");

            $this->reportCompletion('success', $wifiDetails);
        } catch (\Exception $e) {
            Log::error('Error retrieving WiFi details: ' . $e->getMessage());
            $this->reportCompletion('error', $e->getMessage());
        }

        Log::info("Completing GetWifiDetailsJob for task: " . $task['request_id']);

        // Ensure the job stops after handling the task
        return;
    }
}
