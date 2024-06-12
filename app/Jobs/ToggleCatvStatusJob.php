<?php

namespace App\Jobs;

use App\Events\TaskCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class ToggleCatvStatusJob extends BaseTaskJob
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
            $model = $task['model'];
            $newStatus = $task['newStatus'];

            $oltConnector->toggleCatvStatus($port, $onuId, $model, $newStatus);

            Log::info("CATV status toggled for ONU $onuId on port $port.");
            $this->reportCompletion('success', 'CATV status toggled successfully.');
        } catch (\Exception $e) {
            Log::error('Error toggling CATV status: ' . $e->getMessage());
            $this->reportCompletion('error', $e->getMessage());
        }

        // Ensure the job stops after handling the task
        return;
    }
}
