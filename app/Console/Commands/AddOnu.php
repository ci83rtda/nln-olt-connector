<?php

namespace App\Console\Commands;

use App\Services\OltConnector;
use App\Services\Uisp\V1\UispV1Access;
use App\Services\Uisp\V2\UispApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AddOnu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-onu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add an ONU to the OLT';

    protected $uispApi;
    public function __construct()
    {
        parent::__construct();
        $this->uispApi = new UispApi();
    }

    /**
     * Execute the console command.
     */
    public function handle(OltConnector $oltConnector)
    {

        while (true){
            $DeviceUuid = Str::uuid();
            try {
                $this->uispApi->getDevice($DeviceUuid);
            }catch (\Exception $exception){
                break;
            }

        }

        $uispClientId = $this->askWithValidation('Enter the UISP Client ID. e.g: 254');;

        $clientSites = $this->uispApi->getSites([
            'type' => 'endpoint',
            'ucrm' => true,
            'ucrmDetails' => true,
        ]);

        $clientsAddedServices = UispV1Access::doRequest("clients/services?clientId={$uispClientId}&statuses%5B%5D=7");
        $clientsAddedServices = json_decode($clientsAddedServices->getBody()->getContents()) ?? [];
        $filteredData = collect($clientsAddedServices)->where('servicePlanId',24)->first();

        $sites = [];
        $clientSiteData = [];
        $tableData = [];
        foreach ($clientSites as $clientSite) {

            if ($clientSite['identification']['status'] == 'inactive' && $clientSite['identification']['type'] == 'endpoint') {
                if (isset($clientSite['ucrm'])) {
                    if ($clientSite['ucrm']['client']['id'] == $uispClientId) {
                        array_push($sites, $clientSite['id']);
                        $clientSiteData[] = ['id' => $clientSite['id'], 'data' => $clientSite];
                        array_push($tableData, [$clientSite['id'], $clientSite['ucrm']['client']['name'], $clientSite['ucrm']['service']['name']]);
                    }
                }
            }

        }

        $activateCATV = 'no';
        if ($filteredData){
            $activateCATV = $this->choice('This client has a pending CATV service, activate as well?',['yes','no'],'yes');
        }

        $this->table(
            ['Site ID', 'Client', 'Service'],
            $tableData
        );

        $clientSiteId = $this->choice(
            'What is the ID of the client site?',
            $sites,
        );

        $this->info($clientSiteId);

        $clientSiteData = collect($clientSiteData)->where('id', $clientSiteId)->first();

        $clientServiceID = $clientSiteData['data']['ucrm']['service']['id'];
        $clientShortName = $this->shortenName($clientSiteData['data']['ucrm']['client']['name']);
        $wifiName = $this->WifiName($clientSiteData['data']['ucrm']['client']['name']);
        $wifiPassword = $this->generatePassword();


        $port = $this->askWithValidation('Enter the GPON port number (e.g., 5)');
        $serialNumber = $this->askWithValidation('Enter the ONU serial number (e.g., GPON009777F0)');
        $onuType = $this->determineOnuType($serialNumber);

        $params = [];
        $params['description'] = $uispClientId.'-'.$clientServiceID.'-'.$clientShortName;
        $params['vlanid'] = $this->askWithValidation('Enter the VLAN ID');

        if ($onuType === 'vsol') {
            $vendorName = 'Vsol';
            $params['model'] = $this->choice('Enter the model number',['V452','V642'],'V452');
            $params['ip'] = $this->askWithValidation('Enter the static IP address');
            $params['mask'] = $this->getSubnetMask($mascara = $this->askWithValidation('Enter the subnet mask. , 24'));
            $params['gw'] = $this->askWithValidation('Enter the gateway IP address');
            $macaddress = $this->askWithValidation('Enter ONU MAC address');
            $params['dns_master'] = '1.1.1.1';
            $params['dns_slave'] = '8.8.8.8';
            $params['wifi_ssid'] = $wifiName;
            $params['shared_key'] = $wifiPassword;
            if ($activateCATV == 'yes'){
                $params['catv'] = 'enable';
            }else{
                $params['catv'] = $this->choice('Enable CATV?', ['enable', 'disable'], 'disable');
            }
        } elseif ($onuType === 'huawei') {
            $params['model'] = $this->choice('Enter the model number',['EG8145V5','EG8143H5'],'EG8145V5');
            $vendorName = 'Huawei';
            if ($activateCATV == 'yes'){
                $params['video'] = 'unlock';
            }else{
                $params['video'] = $this->choice('Enable video? unlock: enable, lock: disable', ['unlock', 'lock'], 'lock');
            }

        } else {
            $this->error('Unknown ONU type.');
            return 1;
        }

        $modeloOnu = $params['model'];
        if ( $params['model'] == 'V452'){
            $params['model'] = 'VSOLV452';
        }elseif ( $params['model'] == 'V642'){
            $params['model'] = 'VSOLV642';
        }

        $blacboxDevice = $this->blackBox([
            'deviceId' => $DeviceUuid,
            'hostname' => $modeloOnu.'-'.$serialNumber,
            "modelName" => $modeloOnu,
            "systemName" => "pi-monitor",
            "vendorName" => $vendorName,
            "ipAddress" => $params['ip'],
            "macAddress" => $macaddress,
            "deviceRole" => "router",
            "siteId" => $clientSiteId,
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
                    "mac" => $macaddress,
                    "type" => "eth",
                    "addresses" => [$params['ip'].'/'.$mascara]
                ]
            ]
        ]);

        try {
            $createdBlackboxDevice = $this->blackBox($blacboxDevice);
        }catch (\Exception $exception){
            $this->info($exception->getMessage());
        }

        if($clientSiteData['data']['ucrm']['service']['status'] != 1) {
            UispV1Access::doRequest("clients/services/{$clientServiceID}/activate-quoted",'PATCH',[
                'activeFrom' => now()->format('Y-m-d\TH:i:sO'),
                'invoicingStart' => now()->format('Y-m-d\TH:i:sO')
            ]);
        }

        if ($activateCATV == 'yes'){
            UispV1Access::doRequest("clients/services/{$filteredData['id']}/activate-quoted",'PATCH',[
                'activeFrom' => now()->format('Y-m-d\TH:i:sO'),
                'invoicingStart' => now()->format('Y-m-d\TH:i:sO')
            ]);
        }

//        $this->uispApi->createDevicesAuthorization([
//            'siteId' => $clientServiceID,
//            'deviceIds' => [$createdBlackboxDevice['data']['deviceId"']],
//        ]);

        try {
            Log::info('Attempting to connect to OLT');
            $oltConnector->addOnu($port, $serialNumber, $params);
            Log::info('Connected to OLT, adding ONU');
            $this->info('ONU added successfully.');
        } catch (\Exception $e) {
            Log::error('Error adding ONU: ' . $e->getMessage());
            $this->error('Failed to add ONU. Check logs for details.');
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

    private function determineOnuType($serialNumber)
    {
        if (strpos($serialNumber, 'GPON') === 0) {
            return 'vsol';
        } elseif (strpos($serialNumber, 'HWT') === 0) {
            return 'huawei';
        }
        return 'unknown';
    }

    private function shortenName($fullName): string
    {
        $parts = explode(', ', $fullName);
        if (count($parts) == 2) {
            $lastNames = $parts[0];
            $firstNames = $parts[1];

            $fullName = $firstNames . ' ' . $lastNames;
        }



        $nameParts = explode(' ', $fullName);
        $shortenedName = [];

        // Extract first name
        $shortenedName[] = $nameParts[0];

        // Extract second name initial if exists
        if (isset($nameParts[1]) && count($nameParts) > 2) {
            $shortenedName[] = substr($nameParts[1], 0, 1);
        }

        // Extract first last name
        if (count($nameParts) > 2) {
            $shortenedName[] = $nameParts[count($nameParts) - 2];
        }

        // Extract second last name initial
        if (count($nameParts) > 3) {
            $shortenedName[] = substr($nameParts[count($nameParts) - 1], 0, 1);
        }

        // Join parts with hyphen
        return implode('-', $shortenedName);
    }

    public function WifiName($name): string
    {
        // Split the name into parts
        $nameParts = explode(' ', $name);

        // Determine the short name version
        if (count($nameParts) > 1) {
            $lastName = $nameParts[count($nameParts) - 2];
            $initial = substr($nameParts[0], 0, 1);
            $shortName = $lastName . '-' . $initial;
        } else {
            // Handle cases with only one part
            $lastName = $nameParts[0];
            $initial = substr($nameParts[0], 0, 1);
            $shortName = $lastName . '-' . $initial;
        }

        return $shortName;
    }

    public function generatePassword(): string
    {
        $numbers = '0123456789';
        $letters = 'ascbtx';
        $password = '';

        for ($i = 0; $i < 3; $i++) {
            // Add a pair of numbers
            $password .= $numbers[rand(0, 9)];
            $password .= $numbers[rand(0, 9)];

            // Add a pair of letters
            $password .= $letters[rand(0, strlen($letters) - 1)];
            $password .= $letters[rand(0, strlen($letters) - 1)];
        }

        // Add final pair of numbers
        $password .= $numbers[rand(0, 9)];
        $password .= $numbers[rand(0, 9)];

        return $password;
    }

    private function blackBox(array $data)
    {
        return $this->uispApi->createBlackboxDevice($data);
    }

    public function getSubnetMask($cidr): string
    {
        // Check if the provided CIDR is within the valid range (0 to 32)
        if ($cidr < 0 || $cidr > 32) {
            throw new \Exception("CIDR must be between 0 and 32.");
        }

        // Convert CIDR to binary string
        $binaryMask = str_repeat('1', $cidr) . str_repeat('0', 32 - $cidr);

        // Split binary string into 8-bit chunks
        $binaryMaskChunks = str_split($binaryMask, 8);

        // Convert each 8-bit chunk to decimal
        $subnetMask = array_map(function ($chunk) {
            return bindec($chunk);
        }, $binaryMaskChunks);

        // Join the decimal values with dots to form the subnet mask
        return implode('.', $subnetMask);
    }
}
