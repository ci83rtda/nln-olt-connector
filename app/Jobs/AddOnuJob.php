<?php

namespace App\Jobs;

use App\Services\OltConnector;
use Illuminate\Support\Facades\Log;

class AddOnuJob extends BaseTaskJob
{
    /**
     * Execute the job.
     *
     * @param OltConnector $oltConnector
     * @return void
     */
    public function handle(): void
    {

//        $oltConnector = new OltConnector(
//            config('services.olt.host'),
//            config('services.olt.username'),
//            config('services.olt.password'),
//            config('services.olt.enable_password')
//        );

        $task = $this->task;
//        try {
//
//            $result = $oltConnector->checkActivationSerial($task['activationSerial']);
//
//            if ($result['exists']) {
//
//                $port = $result['port'];
//                $onuId = $task['onuId'];
//                $serialNumber = $task['serialNumber'];
//                $profile = $task['profile'];
//                $description = $task['description'];
//
////                $params = [
////                    '' => $task[''],
////                    '' => $task[''],
////                    '' => $task[''],
////                    '' => $task[''],
////                    '' => $task[''],
////                ];
//
////                $oltConnector->addOnu($port, $serialNumber, $params);
//
//            } else {
//
//            }
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

        $this->reportCompletion(3,['ssid'=>$task['wifiName'],'password' => $task['wifiPassword']]);
    }
}
