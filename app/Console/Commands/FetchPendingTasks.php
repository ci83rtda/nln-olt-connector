<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\TaskFetched;
use Illuminate\Support\Facades\Queue;

class FetchPendingTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-pending-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch pending tasks from the central API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = config('services.central_api.url') . 'tasks/pending';
        $token = config('services.central_api.token');
        Log::info($url);
        Log::info($token);
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get($url);

        }catch (ConnectionException $connectionException){
            Log::info($connectionException->getMessage());
        }


        if ($response->successful()) {
            $tasks = $response->json();

            foreach ($tasks as $task) {
                event(new TaskFetched($task));
            }

            $this->info('Pending tasks fetched and dispatched.');
            Log::info('Pending tasks fetched and dispatched.');
        } else {
            $this->error('Failed to fetch pending tasks.');
            Log::error('Failed to fetch pending tasks.');
        }

        return 0;
    }
}
