<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HandleTaskCompletion
{
    /**
     * Handle the event.
     *
     * @param TaskCompleted $event
     * @return void
     */
    public function handle(TaskCompleted $event): void
    {
        $url = config('services.central_api.url') . '/task/callback';

        $response = Http::post($url, [
            'request_id' => $event->requestId,
            'status' => $event->status,
            'message' => $event->message,
        ]);

        if ($response->successful()) {
//            Log::info("Task completion reported for request ID {$event->requestId}.");
        } else {
            Log::error("Failed to report task completion for request ID {$event->requestId}.");
        }
    }
}
