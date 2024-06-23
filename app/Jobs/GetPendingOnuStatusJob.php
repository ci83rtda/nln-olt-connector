<?php

namespace App\Jobs;

use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class GetPendingOnuStatusJob extends BaseTaskJob
{

    /**
     * Execute the job.
     */
    public function handle(OltConnector $oltConnector): void
    {

        $task = $this->task;
        try {
            $activationSerial = $task['activationSerial'];

            $result = $oltConnector->checkActivationSerial($activationSerial);

            Log::info("ONU status retrieved ". json_encode($result));
            $this->reportCompletion(2, $result);
        } catch (\Exception $e) {
            Log::error('Error retrieving ONU status: ' . $e->getMessage());
            $this->reportCompletion(4, ['message' => $e->getMessage()]);
        }

    }
}
