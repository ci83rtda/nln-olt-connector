<?php

namespace App\Console\Commands;

use App\Events\TaskFetched;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPendingTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-pending-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test pending tasks by selecting predefined tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = config('predefined_tasks.tasks');
        $taskNames = array_keys($tasks);

        $selectedTask = $this->choice('Select a task to trigger', $taskNames);

        if (isset($tasks[$selectedTask])) {
            $task = $tasks[$selectedTask];
            event(new TaskFetched($task));

            $this->info("Task '{$task['action']}' has been triggered.");
            Log::info("Task '{$task['action']}' has been triggered.");
        } else {
            $this->error('Invalid task selected.');
            Log::error('Invalid task selected.');
        }

        return 0;
    }
}
