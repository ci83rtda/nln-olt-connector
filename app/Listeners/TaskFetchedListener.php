<?php

namespace App\Listeners;

use App\Events\TaskFetched;
use App\Jobs\AddOnuJob;
use App\Jobs\ChangeWifiSettingsJob;
use App\Jobs\GetOnuStatusJob;
use App\Jobs\GetWifiDetailsJob;
use App\Jobs\ToggleCatvStatusJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TaskFetchedListener
{
    public function handle(TaskFetched $event)
    {
        $task = $event->task;
        Log::info("Handling task: " . $task['action']);

        switch ($task['action']) {
            case 'toggleCatvStatus':
                Log::info("Dispatching ToggleCatvStatusJob for task: " . $task['request_id']);
                ToggleCatvStatusJob::dispatch($task);
                break;
            case 'addOnu':
                Log::info("Dispatching AddOnuJob for task: " . $task['request_id']);
                AddOnuJob::dispatch($task);
                break;
            case 'changeWifiSettings':
                Log::info("Dispatching ChangeWifiSettingsJob for task: " . $task['request_id']);
                ChangeWifiSettingsJob::dispatch($task);
                break;
            case 'getWifiDetails':
                Log::info("Dispatching GetWifiDetailsJob for task: " . $task['request_id']);
                GetWifiDetailsJob::dispatch($task);
                break;
            case 'getOnuStatus':
                Log::info("Dispatching GetOnuStatusJob for task: " . $task['request_id']);
                GetOnuStatusJob::dispatch($task);
                break;
            default:
                Log::error('Unknown task action.');
                break;
        }
    }
}
