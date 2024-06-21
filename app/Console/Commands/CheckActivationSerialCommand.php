<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use Illuminate\Console\Command;

class CheckActivationSerialCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-activation-serial-command {activationSerial}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if an activation serial exists and return the port number';

    /**
     * Execute the console command.
     */
    public function handle(OltConnector $oltConnector)
    {
        $activationSerial = $this->argument('activationSerial');
        $result = $oltConnector->checkActivationSerial($activationSerial);

        if ($result['exists']) {
            $this->info("Activation serial {$activationSerial} exists on port {$result['port']}.");
        } else {
            $this->info("Activation serial {$activationSerial} does not exist.");
        }
    }
}
