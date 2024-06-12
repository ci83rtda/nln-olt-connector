<?php

namespace App\Jobs;

use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class AddOnuJob extends BaseTaskJob
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
            $serialNumber = $task['serialNumber'];
            $profile = $task['profile'];
            $description = $task['description'];

            $oltConnector->addOnu($port, $onuId, $serialNumber, $profile, $description);

            Log::info("ONU added with ID $onuId on port $port.");
            $this->reportCompletion('success', 'ONU added successfully.');
        } catch (\Exception $e) {
            Log::error('Error adding ONU: ' . $e->getMessage());
            $this->reportCompletion('error', $e->getMessage());
        }
    }
}
