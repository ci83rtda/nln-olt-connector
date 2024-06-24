<?php

namespace App\Jobs;

use App\Services\OltConnector;
use App\Services\Uisp\V1\UispV1Access;
use App\Services\Uisp\V2\UispApi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AddOnuJob extends BaseTaskJob
{
    protected $uispApi;

    /**
     * Execute the job.
     *
     * @param OltConnector $oltConnector
     * @return void
     */
    public function handle(): void
    {
        $this->uispApi = new UispApi();

        $oltConnector = new OltConnector(
            config('services.olt.host'),
            config('services.olt.username'),
            config('services.olt.password'),
            config('services.olt.enable_password')
        );

        $taskoriginal = $this->task;
        $task = $taskoriginal['request'];
//        try {
//
//            $result = $oltConnector->checkActivationSerial($task['activationSerial']);

//            if ($result['exists']) {

        $port = $task['onuPort'];
        $activationSerial = $task['activationSerial'];

        if ($task['modelSelection'] == 'v452'){
            $task['model'] = 'VSOLV452';
            $task['catv'] = $task['hasPendingCatv'] ? 'enable' : 'disable';
        }elseif ($task['modelSelection'] == 'v642'){
            $task['model'] = 'VSOLV642';
            $task['catv'] = $task['hasPendingCatv'] ? 'enable' : 'disable';
        }elseif ($task['modelSelection'] == 'EG8145V5'){
            $task['model'] = 'EG8145V5';
        }elseif ($task['modelSelection'] == 'EG8143H5'){
            $task['model'] = 'EG8143H5';
            $task['catv'] = $task['hasPendingCatv'] ? 'unlock' : 'lock';
        }

        $task['description'] = $task['onuDescription'];
        $task['mask'] = $task['subnet'];
        $task['gw'] = $task['gateway'];
        $task['dns_master'] = $task['dns1'];
        $task['dns_slave'] = $task['dns2'];
        $task['wifi_ssid'] = $task['wifiName'];
        $task['shared_key'] = $task['wifiPassword'];

        while (true){
            $deviceUuid = Str::uuid();
            try {
                $this->uispApi->getDevice($deviceUuid);
            }catch (\Exception $exception){
                break;
            }

        }

        $modeloOnu = strtoupper($task['modelSelection']);
        $serialNumber = $activationSerial;
        $vendorName = $task['brand'];
        $macAddress = $task['macAddress'];
        $siteId = $task['siteId'];
        $task['deviceUuid'] = $deviceUuid;

//                $this->reportCompletion(5, $task);
//                return;

        $blacboxDevice = [
            'deviceId' => $deviceUuid,
            'hostname' => $modeloOnu.'-'.$serialNumber,
            "modelName" => $modeloOnu,
            "systemName" => "pi-monitor",
            "vendorName" => $vendorName,
            "ipAddress" => $task['ip'],
            "macAddress" => $macAddress,
            "deviceRole" => "router",
            "siteId" => $siteId,
            "pingEnabled" => false,
            "ubntDevice" => false,
            "ubntData" => [
                "firmwareVersion" => "0",
                "model" => "blackbox"
            ],
            "snmpCommunity" => "public",
            "note" => "Fiber CPE",
            "interfaces" => [
                [
                    "id" => "eth0",
                    "position" => 0,
                    "name" => "eth1",
                    "mac" => $macAddress,
                    "type" => "eth",
                    "addresses" => [$task['ip'].'/'.$task['subnetCidr']]
                ]
            ]
        ];

        try {
            $this->uispApi->createBlackboxDevice($blacboxDevice);
        }catch (\Exception $exception){
            $this->reportCompletion(4, ['message' => $exception->getMessage()]);
            return;
        }


        try {
            Log::info('Attempting to connect to OLT');
            $oltConnector->addOnu($port, $serialNumber, $task);
            Log::info('Connected to OLT, adding ONU');
        } catch (\Exception $e) {
            Log::error('Error adding ONU: ' . $e->getMessage());
            $this->reportCompletion(4, ['message' => $e->getMessage()]);
            return;
        }

        if (in_array($task['catvServiceStatus'],[0,4,7,8]) && $task['activateCatv']){
            UispV1Access::doRequest("clients/services/{$task['catvServiceId']}/activate-quoted",'PATCH',[
                'activeFrom' => now()->timezone('America/Bogota')->format('Y-m-d\TH:i:sO'),
                'invoicingStart' => now()->timezone('America/Bogota')->format('Y-m-d\TH:i:sO')
            ]);
        }

        if (in_array($task['serviceStatus'],[0,4,7,8])){
            UispV1Access::doRequest("clients/services/{$task['serviceId']}/activate-quoted",'PATCH',[
                'activeFrom' => now()->timezone('America/Bogota')->format('Y-m-d\TH:i:sO'),
                'invoicingStart' => now()->timezone('America/Bogota')->format('Y-m-d\TH:i:sO')
            ]);
        }

        $this->reportCompletion(3,['ssid'=>$task['wifiName'],'password' => $task['wifiPassword']]);
    }
}
