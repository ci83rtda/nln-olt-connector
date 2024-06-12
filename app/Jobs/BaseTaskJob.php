<?php

namespace App\Jobs;

use App\Services\OltConnector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\TaskCompleted;

abstract class BaseTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $task;

    /**
     * Create a new job instance.
     *
     * @param array $task
     * @return void
     */
    public function __construct(array $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    abstract public function handle(OltConnector $oltConnector);

    /**
     * Report the task completion to the central API.
     *
     * @param string $status
     * @param string $message
     * @return void
     */
    protected function reportCompletion($status, $message)
    {
        $url = config('services.central_api.url') . '/task/callback';

        $response = Http::post($url, [
            'request_id' => $this->task['request_id'],
            'status' => $status,
            'message' => $message,
        ]);

        if ($response->successful()) {
            Log::info("Task completion reported for request ID {$this->task['request_id']}.");
        } else {
            Log::error("Failed to report task completion for request ID {$this->task['request_id']}.");
        }
    }
}
