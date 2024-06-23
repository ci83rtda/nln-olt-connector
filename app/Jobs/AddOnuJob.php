<?php

namespace App\Jobs;

use App\Services\OltConnector;
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
            $result = $oltConnector->checkActivationSerial($task['activationSerial']);

            if ($result['exists']) {

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
                $params = [
                    'description' => $task['onuDescription'],
                    'vlanid' => $task['vlanid'],
                    'ip' => $task['ip'],
                    'mask' => $task['subnet'],
                    'gw' => $task['gateway'],
                    'dns_master' => $task['dns1'],
                    'dns_slave' => $task['dns2'],
                    'wifi_ssid' => $task['wifiName'],
                    'shared_key' => $task['wifiPassword'],
                    'modelSelection' => $task['modelSelection'],
                    'model' => $task['model'],
                    'catv' => $task['catv'],
                    'hasPendingCatv' => $task['hasPendingCatv'],
                    '' => $task[''],
                ];

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

                $this->reportCompletion(3, $task);
                exit();

                $blacboxDevice = [
                    'deviceId' => $deviceUuid,
                    'hostname' => $modeloOnu.'-'.$serialNumber,
                    "modelName" => $modeloOnu,
                    "systemName" => "pi-monitor",
                    "vendorName" => $vendorName,
                    "ipAddress" => $params['ip'],
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
                            "addresses" => [$params['ip'].'/'.$task['subnetCidr']]
                        ]
                    ]
                ];

                try {

                    $blackboxDeviceResponse = $this->uispApi->createBlackboxDevice($blacboxDevice);
                }catch (\Exception $exception){
//                    $this->info($exception->getMessage());
                }

//
                $oltConnector->addOnu($port, $activationSerial, $params);
//
            } else {
                $this->reportCompletion(5, ['message' => 'ONU no esta en linea']);
            }
//
//
//
//            Log::info("ONU added with ID $onuId on port $port.");
//            $this->reportCompletion('success', 'ONU added successfully.');
//        } catch (\Exception $e) {
//            Log::error('Error adding ONU: ' . $e->getMessage());
//            $this->reportCompletion('error', $e->getMessage());
//        }

        // Ensure the job stops after handling the task

        $this->reportCompletion(3,['ssid'=>$task['request']['wifiName'],'password' => $task['request']['wifiPassword']]);
    }
}
