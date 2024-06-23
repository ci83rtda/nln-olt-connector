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
    abstract public function handle(): void;

    /**
     * Report the task completion to the central API.
     *
     * @param string $status
     * @param array $response
     * @return void
     */
    protected function reportCompletion($status, $response)
    {
        $url = config('services.central_api.url') . 'tasks/'.$this->task['id'];
        $token = config('services.central_api.token');

        $apiResponse = Http::withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->put($url, [
            'status' => $status,
            'response' => $response,
        ]);

        if ($apiResponse->successful()) {
            Log::info("Task completion reported for request ID {$this->task['id']}.");
        } else {
            Log::error("Failed to report task completion for request ID {$this->task['id']}.");
        }
    }
}
