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

class HandleFetchedTask
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(TaskFetched $event)
    {
        $task = $event->task;
        switch ($task['action']) {
            case 'toggleCatvStatus':
                ToggleCatvStatusJob::dispatch($task);
                break;
            case 'addOnu':
                AddOnuJob::dispatch($task);
                break;
            case 'changeWifiSettings':
                ChangeWifiSettingsJob::dispatch($task);
                break;
            case 'getWifiDetails':
                GetWifiDetailsJob::dispatch($task);
                break;
            case 'getOnuStatus':
                GetOnuStatusJob::dispatch($task);
                break;
            default:
                Log::warning("Unknown task action: {$task['action']}");
                break;
        }
    }
}
