<?php

namespace App\Jobs;

use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class GetOnuStatusJob extends BaseTaskJob
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

            $onuStatus = $oltConnector->getOnuStatus($port, $onuId, $asJson);

            Log::info("ONU status retrieved for ONU $onuId on port $port.");
            $this->reportCompletion('success', $onuStatus);
        } catch (\Exception $e) {
            Log::error('Error retrieving ONU status: ' . $e->getMessage());
            $this->reportCompletion('error', $e->getMessage());
        }
    }
}
