<?php

namespace App\Console\Commands;

use App\Services\Uisp\V1\UispV1Access;
use Illuminate\Console\Command;

class GetUISPPendingServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-uisp-pending-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Getting all the clients from UISP.');
        $quotedServices = UispV1Access::doRequest('clients/services?statuses[]=7');

        $quotedServices = json_decode($quotedServices->getBody()->getContents());
        $data = [];
        $tvService = [];

        foreach ($quotedServices as $key => $quotedService) {
            if ($quotedService->servicePlanId == 24) {
                $tvService[] = $quotedService->clientId;
                unset($quotedServices[$key]);
            }
        }

        $quotedServices = array_values($quotedServices);

        foreach ($quotedServices as $quotedService){

            $client = UispV1Access::doRequest('clients/'.$quotedService->clientId);
            $client = json_decode($client->getBody()->getContents());

            $data[] = [
                'service_id' => $quotedService->id,
                'service_name' => $quotedService->servicePlanName,
                'service_aggregate' => in_array($client->id, $tvService) ? 'Con Servicio de CATV' : 'N/A',
                'service_aggregate_trigger' => in_array($client->id, $tvService) ? '1' : '0',
                'customer_id' => $client->id,
                'customer_name' => $client->clientType == 1 ? $client->firstName .' '. $client->lastName : $client->companyContactFirstName .' '. $client->companyContactLastName,
                'identification' => $client->userIdent,

            ];

        }

        $headers = ['ID de Servicio', 'Nombre de Servicio','Servicio Agregado','Servicio Agregado Trigger', 'Id de Cliente', 'Cliente', 'Identificacion'];

        $this->table($headers, $data);

    }
}
