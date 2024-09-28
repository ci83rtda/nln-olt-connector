<?php

namespace App\Listeners;

use App\Events\TaskFetched;
use App\Jobs\AddOnuJob;
use App\Jobs\ChangeWifiSettingsJob;
use App\Jobs\GetOnuStatusJob;
use App\Jobs\GetPendingOnuStatusJob;
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
        //Log::info("Handling task: " . $task['task_type']);

        switch ($task['task_type']) {
            case 3:
                //Log::info("Dispatching ToggleCatvStatusJob for task: " . $task['id']);
                ToggleCatvStatusJob::dispatch($task)->onQueue('tasks');
                break;
            case 2:
//                Log::info("Dispatching AddOnuJob for task: " . $task['id']);
                AddOnuJob::dispatch($task)->onQueue('tasks');
                break;
            case 4:
//                Log::info("Dispatching ChangeWifiSettingsJob for task: " . $task['id']);
                ChangeWifiSettingsJob::dispatch($task)->onQueue('tasks');
                break;
            case 5:
//                Log::info("Dispatching GetWifiDetailsJob for task: " . $task['id']);
                GetWifiDetailsJob::dispatch($task)->onQueue('tasks');
                break;
            case 6:
//                Log::info("Dispatching GetOnuStatusJob for task: " . $task['id']);
                GetOnuStatusJob::dispatch($task)->onQueue('tasks');
                break;
            case 7:
//                Log::info("Dispatching Check ONU status for task: " . $task['id']);
                GetPendingOnuStatusJob::dispatch($task)->onQueue('tasks');
                break;
            default:
//                Log::error('Unknown task action.');
                break;
        }
    }
}
