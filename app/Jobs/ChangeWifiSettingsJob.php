<?php

namespace App\Jobs;

use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class ChangeWifiSettingsJob extends BaseTaskJob
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
            $wifiSettings = $task['wifiSettings'];
            $wifiSwitchSettings = $task['wifiSwitchSettings'];
            $model = $task['model'];

            $oltConnector->changeWifiSettings($port, $onuId, $wifiSettings, $wifiSwitchSettings, $model);

            Log::info("WiFi settings changed for ONU $onuId on port $port.");
            $this->reportCompletion('success', 'WiFi settings changed successfully.');
        } catch (\Exception $e) {
            Log::error('Error changing WiFi settings: ' . $e->getMessage());
            $this->reportCompletion('error', $e->getMessage());
        }

        // Ensure the job stops after handling the task
        return;
    }
}
