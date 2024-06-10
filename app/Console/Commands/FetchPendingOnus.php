<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use Illuminate\Console\Command;

class FetchPendingOnus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-pending-onus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and display pending ONUs from the OLT';

    /**
     * Execute the console command.
     */
    public function handle(OltConnector $oltConnector)
    {
        $this->info('Fetching pending ONUs from the OLT...');

        // Fetch pending ONUs from the OLT
        $pendingOnus = $oltConnector->fetchPendingOnus();

        dd($pendingOnus);

        if (empty($pendingOnus)) {
            $this->info('No pending ONUs found.');
            return 0;
        }

        // Display the pending ONUs
        $this->table(
            ['OnuIndex', 'Sn', 'State', 'Port'],
            $pendingOnus
        );

        return 0;
    }
}
