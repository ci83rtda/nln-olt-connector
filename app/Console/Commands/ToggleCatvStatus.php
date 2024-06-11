<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ToggleCatvStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:toggle-catv-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle the CATV status for a given ONU';

    /**
     * Execute the console command.
     */
    public function handle(OltConnector $oltConnector)
    {
        $port = $this->askWithValidation('Enter the GPON port number (e.g., 5)');
        $onuId = $this->askWithValidation('Enter the ONU ID (e.g., 28)');
        $model = $this->choice('Select the ONU model', ['V452', 'V642', 'EG8143H5']);

        try {
            Log::info('Attempting to toggle CATV status for ONU');
            $oltConnector->toggleCatvStatus($port, $onuId, $model);
            Log::info('CATV status toggled successfully.');
            $this->info('CATV status toggled successfully.');
        } catch (\Exception $e) {
            Log::error('Error toggling CATV status: ' . $e->getMessage());
            $this->error('Failed to toggle CATV status. Check logs for details.');
        }

        return 0;
    }

    private function askWithValidation($question)
    {
        do {
            $response = $this->ask($question);
            if (empty($response)) {
                $this->error('This field is required.');
            }
        } while (empty($response));

        return $response;
    }
}
