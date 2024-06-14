<?php

namespace App\Console\Commands;

use App\Services\Uisp\V1\UispV1Access;
use App\Services\Uisp\V2\UispApi;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $uispApi;
    public function __construct()
    {
        parent::__construct();
        $this->uispApi = new UispApi();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $this->info('Getting all the clients from UISP.');
//        $quotedServices = UispV1Access::doRequest('clients/services?statuses[]=7');
//
//        $quotedServices = json_decode($quotedServices->getBody()->getContents());
//
        $this->info('Getting all the clients from UISP.');
        $quotedServices = UispV1Access::doRequest('clients/281');

        $quotedServices = json_decode($quotedServices->getBody()->getContents());



        $clientSites = $this->uispApi->getSites([
            'type' => 'client',
            'ucrm' => true,
            'ucrmDetails' => true,
        ]);

        $sites = [];
        foreach ($clientSites as $clientSite) {

            if ($clientSite['identification']['status'] == 'inactive') {
                array_push($sites, $clientSite['id']);
            }

        }

        $clientId = $this->choice(
            'What is the ID of the client site?',
            $sites,
        );

        $this->info($clientId);

    }
}
